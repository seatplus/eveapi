<?php


namespace Seatplus\Eveapi\Services\Maintenance;


use Closure;
use Seatplus\Eveapi\Actions\Seatplus\CreateOrUpdateMissingIdsCache;
use Seatplus\Eveapi\Jobs\Seatplus\ResolveUniverseCategoriesByCategoryIdJob;
use Seatplus\Eveapi\Jobs\Seatplus\ResolveUniverseGroupsByGroupIdJob;
use Seatplus\Eveapi\Models\Universe\Group;
use Seatplus\Eveapi\Models\Universe\Type;

class GetMissingCategorysPipe
{
    public function handle($payload, Closure $next)
    {

        $unknown_type_ids = Group::whereDoesntHave('category')->pluck('category_id')->unique()->values();

        if($unknown_type_ids->isNotEmpty()) {
            (new CreateOrUpdateMissingIdsCache('category_ids_to_resolve', $unknown_type_ids))->handle();

            ResolveUniverseCategoriesByCategoryIdJob::dispatch()->onQueue('high');
        }

        return $next($payload);
    }

}
