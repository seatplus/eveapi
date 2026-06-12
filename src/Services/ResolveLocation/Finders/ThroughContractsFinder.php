<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Services\ResolveLocation\Finders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Seatplus\Eveapi\Models\Contracts\Contract;
use Seatplus\Eveapi\Models\RefreshToken;

class ThroughContractsFinder implements FinderInterface
{
    #[\Override]
    public function handle(int $locationId, Collection $tracings): ?RefreshToken
    {
        $characterIdsToIgnore = $tracings->pluck('character_id');

        return Contract::query()
            ->where(fn (Builder $query) => $query
                ->where('start_location_id', $locationId)
                ->orWhere('end_location_id', $locationId)
            )
            ->where(fn (Builder $query) => $query
                ->whereHas('issuerCharacter.refreshToken')
                ->orWhereHas('assigneeCharacter.refreshToken')
            )
            ->where(fn (Builder $query) => $query
                ->whereNotIn('issuer_id', $characterIdsToIgnore)
                ->orWhereNotIn('assignee_id', $characterIdsToIgnore)
            )
            ->inRandomOrder()
            ->get()
            ->map(fn (Contract $contract) => [
                $contract->issuerCharacter?->refreshToken,
                $contract->assigneeCharacter?->refreshToken,
            ])
            ->flatten()
            ->unique()
            ->filter()
            ->filter(fn (RefreshToken $refreshToken) => $refreshToken->hasScope('esi-universe.read_structures.v1'))
            ->first();
    }
}
