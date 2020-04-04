<?php


namespace Seatplus\Eveapi\Services\Pipes;


use Closure;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetJob;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetsLocationJob;
use Seatplus\Eveapi\Jobs\Seatplus\ResolveUniverseCategoriesByCategoryIdJob;
use Seatplus\Eveapi\Jobs\Seatplus\ResolveUniverseGroupsByGroupIdJob;
use Seatplus\Eveapi\Jobs\Seatplus\ResolveUniverseTypesByTypeIdJob;

class CharacterAssets implements Pipe
{

    public function handle($job_container, Closure $next)
    {

        if(in_array('esi-assets.read_assets.v1', $job_container->refresh_token->scopes))
            //TODO with refactoring to use events: rework this
            CharacterAssetJob::withChain([
                new CharacterAssetsLocationJob($job_container),
                new ResolveUniverseTypesByTypeIdJob,
                new ResolveUniverseGroupsByGroupIdJob,
                new ResolveUniverseCategoriesByCategoryIdJob,
            ])->dispatch($job_container)->onQueue('default');

        return  $next($job_container);

    }
}
