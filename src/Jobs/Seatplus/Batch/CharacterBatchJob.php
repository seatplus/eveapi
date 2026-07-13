<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Jobs\Seatplus\Batch;

use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimitedWithRedis;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetJob;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetsNameJob;
use Seatplus\Eveapi\Jobs\Character\CharacterAffiliationJob;
use Seatplus\Eveapi\Jobs\Character\CharacterInfoJob;
use Seatplus\Eveapi\Jobs\Character\CharacterRoleJob;
use Seatplus\Eveapi\Jobs\Character\CorporationHistoryJob;
use Seatplus\Eveapi\Jobs\Contacts\AllianceContactJob;
use Seatplus\Eveapi\Jobs\Contacts\AllianceContactLabelJob;
use Seatplus\Eveapi\Jobs\Contacts\CharacterContactJob;
use Seatplus\Eveapi\Jobs\Contacts\CharacterContactLabelJob;
use Seatplus\Eveapi\Jobs\Contacts\CorporationContactJob;
use Seatplus\Eveapi\Jobs\Contacts\CorporationContactLabelJob;
use Seatplus\Eveapi\Jobs\Contracts\CharacterContractsJob;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\EnrichAssetTypeGroupCategoryJob;
use Seatplus\Eveapi\Jobs\Mail\MailHeaderJob;
use Seatplus\Eveapi\Jobs\Skills\SkillQueueJob;
use Seatplus\Eveapi\Jobs\Skills\SkillsJob;
use Seatplus\Eveapi\Jobs\Wallet\CharacterBalanceJob;
use Seatplus\Eveapi\Jobs\Wallet\CharacterWalletJournalJob;
use Seatplus\Eveapi\Jobs\Wallet\CharacterWalletTransactionJob;
use Seatplus\Eveapi\Models\BatchStatistic;
use Seatplus\Eveapi\Models\BatchUpdate;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\RefreshToken;

class CharacterBatchJob implements ShouldBeUnique, ShouldQueue
{
    use Batchable;
    use Queueable;

    const int REFRESH_DELAY_MINUTES = 5;

    public RefreshToken $refreshToken;

    private array $batchJobs;

    public function __construct(
        public int $characterId,
        public $queue = 'default', // @pest-ignore-type
        array $batchJobs = [],
        public bool $reschedule = false,
        public bool $force = false,
    ) {
        $this->refreshToken = RefreshToken::find($this->characterId);
        $this->batchJobs = $batchJobs ?: $this->createBatchJobs();
    }

    public function middleware(): array
    {
        return [
            (new RateLimitedWithRedis('character_batch'))->dontRelease(),
        ];
    }

    public function uniqueId(): string
    {
        return $this->characterId.':'.$this->queue;
    }

    public function handle(): void
    {
        // 1. Get BatchUpdate Entry
        $batchUpdate = $this->getBatchUpdate();

        // Force paths (scope change, seatplus:update-character) always run; only the scheduled
        // catch-up defers to a previous batch that is genuinely still in flight.
        if (! $this->force && $this->shouldDiscardUpdate($batchUpdate)) {
            return;
        }
        $this->resetBatchUpdate($batchUpdate);

        // 3. Dispatch and Return Job
        $batch = $this->execute();

        BatchStatistic::createEntry($batch);

        $this->updateBatchId($batch, $batchUpdate);
    }

    private function execute(): Batch
    {
        $character = $this->refreshToken?->character->name ?? $this->characterId;
        $batchName = sprintf('%s (character) update batch', $character);
        $characterId = $this->characterId;
        $queue = $this->queue;
        $reschedule = $this->reschedule;

        return Bus::batch($this->getBatchJobs())
            ->finally(function (Batch $batch) use ($characterId, $queue, $reschedule) {
                // @codeCoverageIgnoreStart
                BatchUpdate::where('batch_id', $batch->id)->update(['finished_at' => now()]);
                BatchStatistic::where('batch_id', $batch->id)->update(['finished_at' => now()]);

                if ($reschedule) {
                    CharacterBatchJob::dispatch($characterId, $queue, reschedule: true)
                        ->delay(now()->addMinutes(self::REFRESH_DELAY_MINUTES));
                }
                // @codeCoverageIgnoreEnd
            })
            ->name($batchName)
            ->onQueue($this->queue)
            ->allowFailures()
            ->dispatch();
    }

    private function createBatchJobs(): array
    {
        return [
            // Add Private Endpoints
            [
                // Chain character info and affiliation
                new CharacterInfoJob($this->characterId),
                new CharacterAffiliationJob($this->characterId),
            ],
            new CorporationHistoryJob($this->characterId),
            ...$this->addAssetsJobs(),
            ...$this->addCharacterRolesJobs(),
            ...$this->addContactsJobs(),
            ...$this->addWalletJobs(),
            ...$this->addContractJobs(),
            ...$this->addSkillsJobs(),
            ...$this->addSkillQueueJobs(),
            ...$this->addMailsJobs(),
        ];
    }

    private function addAssetsJobs(): array
    {
        // Return empty array if required scopes are not present
        if (! $this->refreshToken->hasScope('esi-assets.read_assets.v1')) {
            return [];
        }

        return [
            // add chain of jobs to get assets
            [
                new CharacterAssetJob($this->characterId),
                new CharacterAssetsNameJob($this->characterId),
                new EnrichAssetTypeGroupCategoryJob,
            ],
        ];
    }

    private function addCharacterRolesJobs(): array
    {
        // Return empty array if required scopes are not present
        if (! $this->refreshToken->hasScope('esi-characters.read_corporation_roles.v1')) {
            return [];
        }

        return [
            new CharacterRoleJob($this->characterId),
        ];
    }

    private function addContactsJobs(): array
    {
        return collect()
            ->merge($this->addCharacterContactsJobs())
            ->merge($this->addCorporationContactsJobs())
            ->merge($this->addAllianceContactsJobs())
            ->values()
            ->toArray();
    }

    private function addCharacterContactsJobs(): array
    {
        // Return empty array if required scopes are not present
        if (! $this->refreshToken->hasScope('esi-characters.read_contacts.v1')) {
            return [];
        }

        return [
            [
                new CharacterContactJob($this->characterId),
                new CharacterContactLabelJob($this->characterId),
            ],
        ];
    }

    private function addCorporationContactsJobs(): array
    {
        // Return empty array if required scopes are not present
        if (! $this->refreshToken->hasScope('esi-corporations.read_contacts.v1')) {
            return [];
        }

        // Get corporation_id from character
        $corporationId = $this->refreshToken->character?->corporation_id;

        return [
            [
                new CorporationContactJob($corporationId, $this->characterId),
                new CorporationContactLabelJob($corporationId, $this->characterId),
            ],
        ];
    }

    private function addAllianceContactsJobs(): array
    {
        // Return empty array if required scopes are not present
        if (! $this->refreshToken->hasScope('esi-alliances.read_contacts.v1')) {
            return [];
        }

        // Get alliance_id from character
        $allianceId = $this->refreshToken->character?->alliance_id;

        // Return empty array if character has no alliance
        if (! $allianceId) {
            return [];
        }

        return [
            [
                new AllianceContactJob($allianceId, $this->characterId),
                new AllianceContactLabelJob($allianceId, $this->characterId),
            ],
        ];
    }

    private function addWalletJobs(): array
    {
        // Return empty array if required scopes are not present
        if (! $this->refreshToken->hasScope('esi-wallet.read_character_wallet.v1')) {
            return [];
        }

        return [
            new CharacterWalletJournalJob($this->characterId),
            new CharacterWalletTransactionJob($this->characterId),
            new CharacterBalanceJob($this->characterId),
        ];
    }

    private function addContractJobs(): array
    {
        // Return empty array if required scopes are not present
        if (! $this->refreshToken->hasScope('esi-contracts.read_character_contracts.v1')) {
            return [];
        }

        return [
            new CharacterContractsJob($this->characterId),
        ];
    }

    private function addSkillsJobs(): array
    {
        // Return empty array if required scopes are not present
        if (! $this->refreshToken->hasScope('esi-skills.read_skills.v1')) {
            return [];
        }

        return [
            new SkillsJob($this->characterId),
        ];
    }

    private function addSkillQueueJobs(): array
    {
        // Return empty array if required scopes are not present
        if (! $this->refreshToken->hasScope('esi-skills.read_skillqueue.v1')) {
            return [];
        }

        return [
            new SkillQueueJob($this->characterId),
        ];
    }

    private function addMailsJobs(): array
    {
        // Return empty array if required scopes are not present
        if (! $this->refreshToken->hasScope('esi-mail.read_mail.v1')) {
            return [];
        }

        return [
            new MailHeaderJob($this->characterId),
        ];
    }

    public function getBatchJobs(): array
    {
        return $this->batchJobs;
    }

    public function getBatchUpdate(): BatchUpdate
    {
        return BatchUpdate::firstOrCreate([
            'batchable_id' => $this->characterId,
            'batchable_type' => CharacterInfo::class,
        ]);
    }

    private function shouldDiscardUpdate(BatchUpdate $batchUpdate): bool
    {
        if (! $batchUpdate->is_pending) {
            return false;
        }

        // No time cap — a batch can legitimately run for a long time on large installs, so we
        // never interrupt one by the clock. Skip only while the previous batch genuinely still
        // exists and is in flight (neither finished nor cancelled). A crashed/pruned batch — or
        // a force dispatch — falls through and re-runs, so a character is never silenced forever.
        return $batchUpdate->batch_id !== null
            && DB::table('job_batches')
                ->where('id', $batchUpdate->batch_id)
                ->whereNull('finished_at')
                ->whereNull('cancelled_at')
                ->exists();
    }

    public function resetBatchUpdate(BatchUpdate $batchUpdate): void
    {
        // reset batch_id, finished_at, started_at and queue
        $batchUpdate->finished_at = null;
        $batchUpdate->batch_id = null;
        $batchUpdate->started_at = now();
        $batchUpdate->queue = $this->queue;
    }

    public function updateBatchId(Batch $batch, mixed $batchUpdate): void
    {
        $batchUpdate->batch_id = $batch->id;
        $batchUpdate->save();
    }
}
