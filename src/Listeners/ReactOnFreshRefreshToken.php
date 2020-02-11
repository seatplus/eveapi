<?php


namespace Seatplus\Eveapi\Listeners;


use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Events\RefreshTokenSaved;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetJob;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetsLocationJob;
use Seatplus\Eveapi\Jobs\Character\CharacterInfo;
use Seatplus\Eveapi\Jobs\Character\CharacterRoleJob;
use Seatplus\Eveapi\Jobs\Seatplus\ResolveUniverseCategoriesByCategoryIdJob;
use Seatplus\Eveapi\Jobs\Seatplus\ResolveUniverseGroupsByGroupIdJob;
use Seatplus\Eveapi\Jobs\Seatplus\ResolveUniverseTypesByTypeIdJob;

class ReactOnFreshRefreshToken
{
    public function handle(RefreshTokenSaved $refresh_token_event)
    {

        $job_container = new JobContainer([
            'refresh_token' => $refresh_token_event->refresh_token->refresh()
        ]);

        //TODO queue all character job and before corporation with chain, the members to check if user has access to certain corp informations.
        CharacterInfo::dispatch($job_container)->onQueue('high');

        CharacterAssetJob::withChain([
            new CharacterAssetsLocationJob($job_container),
            new ResolveUniverseTypesByTypeIdJob,
            new ResolveUniverseGroupsByGroupIdJob,
            new ResolveUniverseCategoriesByCategoryIdJob,
        ])->dispatch($job_container)->onQueue('high');

        CharacterRoleJob::dispatch($job_container)->onQueue('high');
    }

}
