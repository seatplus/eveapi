<?php

namespace Seatplus\Eveapi\Jobs\Seatplus\Batch;

use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Redis;
use Seatplus\Eveapi\Containers\JobContainer;
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

    private RefreshToken $refresh_token;
    private JobContainer $job_container;
    private array $batch_jobs;

    public function __construct(
        public int $character_id,
        public $queue = 'default'
    )
    {
        $this->refresh_token = RefreshToken::find($this->character_id);
        $this->createBatchJobs();
    }

    public function uniqueId()
    {

        return $this->character_id . ':' . $this->queue;
    }

    public function handle()
    {
        Redis::throttle($this->character_id . ":" . $this->queue)
            ->block(0)
            ->allow(1)
            ->every(60 * 60)
            ->then(function () {
                BatchUpdate::query()
                    ->where('batchable_id', $this->character_id)
                    ->delete();

                // 1. Get BatchUpdate Entry
                $batch_update = BatchUpdate::firstOrCreate([
                    'batchable_id' => $this->character_id,
                    'batchable_type' => CharacterInfo::class,
                ]);

                // 2. Check if it is still pending
                // Discard update if still pending or finished at is younger than 60 minutes
                if ($batch_update->is_pending && now()->isSameDay($batch_update->started_at)) {
                    return;
                }

                // reset batch_id, finished_at and started_at
                $batch_update->finished_at = null;
                $batch_update->batch_id = null;
                $batch_update->started_at = now();

                // 3. Dispatch and Return Job
                $batch = $this->execute();

                $batch_update->batch_id = $batch->id;
                $batch_update->save();
            }, function () {
                $this->delete();
            });
    }

    private function execute() : Batch
    {
        $character = $this->refresh_token?->character?->name ?? $this->character_id;
        $batch_name = sprintf('%s (character) update batch', $character);


        return Bus::batch($this->getBatchJobs())
            ->finally(function (Batch $batch) {
                BatchUpdate::where('batch_id', $batch->id)->update(['finished_at' => now()]);
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

    private function addAssetsJobs() : array
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
            ]
        ];
    }

    private function addCharacterRolesJobs() : array
    {

        // Return empty array if required scopes are not present
        if (! $this->refresh_token->hasScope('esi-characters.read_corporation_roles.v1')) {
            return [];
        }

        return [
            new CharacterRoleJob($this->character_id),
        ];
    }

    private function addContactsJobs() : array
    {

        return collect()
            ->merge($this->addCharacterContactsJobs())
            ->merge($this->addCorporationContactsJobs())
            ->merge($this->addAllianceContactsJobs())
            ->values()
            ->toArray();
    }

    private function addCharacterContactsJobs() : array
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

    private function addCorporationContactsJobs() : array
    {

        // Return empty array if required scopes are not present
        if (! $this->refresh_token->hasScope('esi-corporations.read_contacts.v1')) {
            return [];
        }

        return [
            [
                new CorporationContactJob($this->character_id),
                new CorporationContactLabelJob($this->character_id),
            ],
        ];
    }

    private function addAllianceContactsJobs() : array
    {

        // Return empty array if required scopes are not present
        if (! $this->refresh_token->hasScope('esi-alliances.read_contacts.v1')) {
            return [];
        }

        return [
            [
                new AllianceContactJob($this->character_id),
                new AllianceContactLabelJob($this->character_id),
            ],
        ];
    }

    private function addWalletJobs() : array
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

    private function addContractJobs() : array
    {

        // Return empty array if required scopes are not present
        if (! $this->refresh_token->hasScope('esi-contracts.read_character_contracts.v1')) {
            return [];
        }

        return [
            new CharacterContractsJob($this->character_id)
        ];
    }

    private function addSkillsJobs() : array
    {

        // Return empty array if required scopes are not present
        if (! $this->refresh_token->hasScope('esi-skills.read_skills.v1')) {
            return [];
        }

        return [
            new SkillsJob($this->character_id)
        ];
    }

    private function addSkillQueueJobs() : array
    {

        // Return empty array if required scopes are not present
        if (! $this->refresh_token->hasScope('esi-skills.read_skillqueue.v1')) {
            return [];
        }

        return [
            new SkillQueueJob($this->character_id)
        ];
    }

    private function addMailsJobs() : array
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
