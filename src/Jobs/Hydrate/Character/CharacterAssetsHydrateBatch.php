<?php


namespace Seatplus\Eveapi\Jobs\Hydrate\Character;


use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetJob;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetsNameJob;

class CharacterAssetsHydrateBatch extends HydrateCharacterBase
{
    public function __construct(JobContainer $job_container)
    {
        parent::__construct($job_container);

        parent::setRequiredScope('esi-assets.read_assets.v1');
    }

    public function handle()
    {

        if($this->hasRequiredScope())
            $this->batch()->add([
                [
                    new CharacterAssetJob($this->job_container),
                    new CharacterAssetsNameJob($this->job_container)
                ]
            ]);

    }
}
