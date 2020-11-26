<?php


namespace Seatplus\Eveapi\Jobs\Hydrate\Character;


use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetJob;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetsNameJob;
use Seatplus\Eveapi\Jobs\Character\CharacterRoleJob;

class CharacterRolesHydrateBatch extends HydrateCharacterBase
{
    public function __construct(JobContainer $job_container)
    {
        parent::__construct($job_container);

        parent::setRequiredScope('esi-characters.read_corporation_roles.v1');
    }

    public function handle()
    {

        if($this->hasRequiredScope())
            $this->batch()->add([
                new CharacterRoleJob($this->job_container),
            ]);

    }
}
