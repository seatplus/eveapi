<?php


namespace Seatplus\Eveapi\Services\Maintenance;


use Closure;
use Illuminate\Database\Eloquent\Builder;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Models\Assets\CharacterAsset;
use Seatplus\Eveapi\Models\RefreshToken;

class GetMissingLocationFromAssetsPipe
{
    public function handle($payload, Closure $next)
    {

        CharacterAsset::doesntHave('location')
            ->AssetsLocationIds()
            ->inRandomOrder()
            ->addSelect('character_id')
            ->get()
            ->each(function ($asset) {

                $refresh_token = RefreshToken::find($asset->character_id);

                dispatch(new ResolveLocationJob($asset->location_id, $refresh_token))->onQueue('high');
            });

        return $next($payload);
    }

}
