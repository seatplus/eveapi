<?php


namespace Seatplus\Eveapi\Services\Maintenance;


use Closure;
use Seatplus\Eveapi\Actions\Seatplus\CreateOrUpdateMissingIdsCache;
use Seatplus\Eveapi\Jobs\Seatplus\ResolveUniverseTypesByTypeIdJob;
use Seatplus\Eveapi\Models\Assets\CharacterAsset;

class GetMissingTypesFromCharacterAssetsPipe
{
    public function handle($payload, Closure $next)
    {

        $type_ids = CharacterAsset::doesntHave('type')->pluck('type_id')->unique()->values();

        if($type_ids->isNotEmpty())
            (new CreateOrUpdateMissingIdsCache('type_ids_to_resolve', $type_ids))->handle();

        ResolveUniverseTypesByTypeIdJob::dispatch()->onQueue('high');

        return $next($payload);
    }

}
