<?php

namespace Seatplus\Eveapi\Services\ResolveLocation\Finders;

use Illuminate\Database\Eloquent\Collection;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\RefreshToken;

class ThroughCharacterAssetsFinder implements FinderInterface
{
    #[\Override]
    public function handle(int $location_id, Collection $tracings): ?RefreshToken
    {

        $character_ids_to_ignore = $tracings->pluck('character_id');

        return Asset::query()
            ->whereHas('assetable.refresh_token')
            ->where('location_id', $location_id)
            ->whereNotIn('assetable_id', $character_ids_to_ignore)
            ->where('assetable_type', CharacterInfo::class)
            ->inRandomOrder()
            ->get()
            ->map(fn (Asset $asset) => $asset->assetable->refresh_token)
            ->unique()
            // filter refresh token that has scope esi-universe.read_structures.v1
            ->filter(fn (RefreshToken $refresh_token) => $refresh_token->hasScope('esi-universe.read_structures.v1'))
            ->first();

    }
}
