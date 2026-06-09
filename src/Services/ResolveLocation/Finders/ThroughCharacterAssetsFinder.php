<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Services\ResolveLocation\Finders;

use Illuminate\Database\Eloquent\Collection;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\RefreshToken;

class ThroughCharacterAssetsFinder implements FinderInterface
{
    #[\Override]
    public function handle(int $locationId, Collection $tracings): ?RefreshToken
    {

        $characterIdsToIgnore = $tracings->pluck('character_id');

        return Asset::query()
            ->whereHas('assetable.refreshToken')
            ->where('location_id', $locationId)
            ->whereNotIn('assetable_id', $characterIdsToIgnore)
            ->where('assetable_type', CharacterInfo::class)
            ->inRandomOrder()
            ->get()
            ->map(fn (Asset $asset) => data_get($asset, 'assetable.refreshToken'))
            ->unique()
            // filter refresh token that has scope esi-universe.read_structures.v1
            ->filter(fn (RefreshToken $refreshToken) => $refreshToken->hasScope('esi-universe.read_structures.v1'))
            ->first();

    }
}
