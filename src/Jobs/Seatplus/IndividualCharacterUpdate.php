<?php

namespace Seatplus\Eveapi\Jobs\Seatplus;

use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
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

class IndividualCharacterUpdate implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(protected JobContainer $jobContainer)
    {
    }

    public function tags()
    {
        return [self::class, $this->jobContainer->getCharacterId()];
    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->jobContainer->getCharacterId()))->dontRelease()];
    }

    public function handle()
    {
        $character = optional($this->jobContainer->refresh_token->refresh()->character)->name ?? $this->jobContainer->refresh_token->character_id;

        Bus::batch([

            // Public endpoint hence no hydration or added logic required
            new CharacterInfoJob($this->jobContainer),
            new CorporationHistoryJob($this->jobContainer),

            new CharacterAssetsHydrateBatch($this->jobContainer),
            new CharacterRolesHydrateBatch($this->jobContainer),
            new ContactHydrateBatch($this->jobContainer),
            new WalletHydrateBatch($this->jobContainer),
            new ContractHydrateBatch($this->jobContainer),
            new SkillsHydrateBatch($this->jobContainer),
            new MailsHydrateBatch($this->jobContainer),

        ])->then(
            fn (Batch $batch) => logger()->info("Character update batch of ${character} processed!")
        )->name("${character} (character) update batch")->onQueue($this->jobContainer->queue)
            ->onConnection(config('queue.default'))
            ->allowFailures()->dispatch();
    }
}
