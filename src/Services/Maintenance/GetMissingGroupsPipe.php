<?php


namespace Seatplus\Eveapi\Services\Maintenance;


use Closure;
use Seatplus\Eveapi\Actions\Seatplus\CreateOrUpdateMissingIdsCache;
use Seatplus\Eveapi\Jobs\Seatplus\ResolveUniverseGroupsByGroupIdJob;
use Seatplus\Eveapi\Models\Universe\Type;

class GetMissingGroupsPipe
{
    public function handle($payload, Closure $next)
    {

        $unknown_type_ids = Type::whereDoesntHave('group')->pluck('group_id')->unique()->values();

        if($unknown_type_ids->isNotEmpty()) {
            (new CreateOrUpdateMissingIdsCache('group_ids_to_resolve', $unknown_type_ids))->handle();

            ResolveUniverseGroupsByGroupIdJob::dispatch()->onQueue('high');
        }

        return $next($payload);
    }

}
