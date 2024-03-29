<?php

namespace Seatplus\Eveapi\Jobs\Seatplus\Batch;

use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimitedWithRedis;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetJob;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetsNameJob;
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

class CharacterBatchJob implements ShouldQueue, ShouldBeUnique
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public RefreshToken $refresh_token;

    private array $batch_jobs;

    public function __construct(
        public int $character_id,
        public $queue = 'default'
    ) {
        $this->refresh_token = RefreshToken::find($this->character_id);
        $this->createBatchJobs();
    }

    public function middleware()
    {
        return [
            (new RateLimitedWithRedis('character_batch'))->dontRelease(),
        ];
    }

    public function uniqueId()
    {
        return $this->character_id.':'.$this->queue;
    }

    public function handle()
    {
        // 1. Get BatchUpdate Entry
        $batch_update = BatchUpdate::firstOrCreate([
            'batchable_id' => $this->character_id,
            'batchable_type' => CharacterInfo::class,
        ]);

        // Discard update if still pending
        if ($batch_update->is_pending && now()->isSameHour($batch_update->started_at)) {
            return;
        }

        // Discard update if finished in last hour
        if ($batch_update->finished_at && now()->isSameHour($batch_update->finished_at)) {
            return;
        }

        // reset batch_id, finished_at and started_at
        $batch_update->finished_at = null;
        $batch_update->batch_id = null;
        $batch_update->started_at = now();

        // 3. Dispatch and Return Job
        $batch = $this->execute();

        BatchStatistic::createEntry($batch);

        $batch_update->batch_id = $batch->id;
        $batch_update->save();
    }

    private function execute(): Batch
    {
        $character = $this->refresh_token?->character?->name ?? $this->character_id;
        $batch_name = sprintf('%s (character) update batch', $character);

        return Bus::batch($this->getBatchJobs())
            ->finally(function (Batch $batch) {
                BatchUpdate::where('batch_id', $batch->id)->update(['finished_at' => now()]);
                BatchStatistic::where('batch_id', $batch->id)->update(['finished_at' => now()]);
            })
            ->name($batch_name)
            ->onQueue($this->queue)
            ->allowFailures()
            ->dispatch();
    }

    private function createBatchJobs(): void
    {
        $this->batch_jobs = collect()
            // Add Public Endpoints
            ->merge([
                new CharacterInfoJob($this->character_id),
                new CorporationHistoryJob($this->character_id),
            ])
            ->merge($this->addAssetsJobs())
            ->merge($this->addCharacterRolesJobs())
            ->merge($this->addContactsJobs())
            ->merge($this->addWalletJobs())
            ->merge($this->addContractJobs())
            ->merge($this->addSkillsJobs())
            ->merge($this->addSkillQueueJobs())
            ->merge($this->addMailsJobs())
            ->values()
            ->toArray();
    }

    private function addAssetsJobs(): array
    {
        // Return empty array if required scopes are not present
        if (! $this->refresh_token->hasScope('esi-assets.read_assets.v1')) {
            return [];
        }

        return [
            // add chain of jobs to get assets
            [
                new CharacterAssetJob($this->character_id),
                new CharacterAssetsNameJob($this->character_id),
            ],
        ];
    }

    private function addCharacterRolesJobs(): array
    {
        // Return empty array if required scopes are not present
        if (! $this->refresh_token->hasScope('esi-characters.read_corporation_roles.v1')) {
            return [];
        }

        return [
            new CharacterRoleJob($this->character_id),
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
        if (! $this->refresh_token->hasScope('esi-characters.read_contacts.v1')) {
            return [];
        }

        return [
            [
                new CharacterContactJob($this->character_id),
                new CharacterContactLabelJob($this->character_id),
            ],
        ];
    }

    private function addCorporationContactsJobs(): array
    {
        // Return empty array if required scopes are not present
        if (! $this->refresh_token->hasScope('esi-corporations.read_contacts.v1')) {
            return [];
        }

        // Get corporation_id from character
        $corporation_id = $this->refresh_token->character->corporation_id;

        return [
            [
                new CorporationContactJob($corporation_id, $this->character_id),
                new CorporationContactLabelJob($corporation_id, $this->character_id),
            ],
        ];
    }

    private function addAllianceContactsJobs(): array
    {
        // Return empty array if required scopes are not present
        if (! $this->refresh_token->hasScope('esi-alliances.read_contacts.v1')) {
            return [];
        }

        // Get alliance_id from character
        $alliance_id = $this->refresh_token->character->alliance_id;

        // Return empty array if character has no alliance
        if (! $alliance_id) {
            return [];
        }

        return [
            [
                new AllianceContactJob($alliance_id, $this->character_id),
                new AllianceContactLabelJob($alliance_id, $this->character_id),
            ],
        ];
    }

    private function addWalletJobs(): array
    {
        // Return empty array if required scopes are not present
        if (! $this->refresh_token->hasScope('esi-wallet.read_character_wallet.v1')) {
            return [];
        }

        return [
            new CharacterWalletJournalJob($this->character_id),
            new CharacterWalletTransactionJob($this->character_id),
            new CharacterBalanceJob($this->character_id),
        ];
    }

    private function addContractJobs(): array
    {
        // Return empty array if required scopes are not present
        if (! $this->refresh_token->hasScope('esi-contracts.read_character_contracts.v1')) {
            return [];
        }

        return [
            new CharacterContractsJob($this->character_id),
        ];
    }

    private function addSkillsJobs(): array
    {
        // Return empty array if required scopes are not present
        if (! $this->refresh_token->hasScope('esi-skills.read_skills.v1')) {
            return [];
        }

        return [
            new SkillsJob($this->character_id),
        ];
    }

    private function addSkillQueueJobs(): array
    {
        // Return empty array if required scopes are not present
        if (! $this->refresh_token->hasScope('esi-skills.read_skillqueue.v1')) {
            return [];
        }

        return [
            new SkillQueueJob($this->character_id),
        ];
    }

    private function addMailsJobs(): array
    {
        // Return empty array if required scopes are not present
        if (! $this->refresh_token->hasScope('esi-mail.read_mail.v1')) {
            return [];
        }

        return [
            new MailHeaderJob($this->character_id),
        ];
    }

    public function getBatchJobs(): array
    {
        return $this->batch_jobs;
    }
}
