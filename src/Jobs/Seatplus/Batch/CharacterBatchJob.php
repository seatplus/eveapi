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
use Seatplus\Eveapi\Jobs\Character\CharacterInfoJob;
use Seatplus\Eveapi\Jobs\Character\CorporationHistoryJob;
use Seatplus\Eveapi\Jobs\Hydrate\Character\CharacterAssetsHydrateBatch;
use Seatplus\Eveapi\Jobs\Hydrate\Character\CharacterRolesHydrateBatch;
use Seatplus\Eveapi\Jobs\Hydrate\Character\ContactHydrateBatch;
use Seatplus\Eveapi\Jobs\Hydrate\Character\ContractHydrateBatch;
use Seatplus\Eveapi\Jobs\Hydrate\Character\MailsHydrateBatch;
use Seatplus\Eveapi\Jobs\Hydrate\Character\SkillsHydrateBatch;
use Seatplus\Eveapi\Jobs\Hydrate\Character\WalletHydrateBatch;
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

    public function __construct(public int $character_id)
    {
        $this->refresh_token = RefreshToken::find($this->character_id);
        $this->job_container = new JobContainer(['refresh_token' => $this->refresh_token, 'queue' => $this->queue]);
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
            ->every(60*60)
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

        return Bus::batch([

            // Public endpoint hence no hydration or added logic required
            new CharacterInfoJob($this->job_container),
            new CorporationHistoryJob($this->job_container),

            new CharacterAssetsHydrateBatch($this->job_container),
            new CharacterRolesHydrateBatch($this->job_container),
            new ContactHydrateBatch($this->job_container),
            new WalletHydrateBatch($this->job_container),
            new ContractHydrateBatch($this->job_container),
            new SkillsHydrateBatch($this->job_container),
            new MailsHydrateBatch($this->job_container),

        ])
            ->finally(function (Batch $batch) {
                BatchUpdate::where('batch_id', $batch->id)->update(['finished_at' => now()]);
            })
            ->name($batch_name)
            ->onQueue($this->job_container->queue)
            ->allowFailures()
            ->dispatch();
    }

}