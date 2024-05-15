<?php

namespace Seatplus\Eveapi\Services\ResolveLocation\Finders;

use Closure;
use Illuminate\Database\Eloquent\Collection;
use Seatplus\Eveapi\Models\Contracts\Contract;
use Seatplus\Eveapi\Models\RefreshToken;

class ThroughContractsFinder implements FinderInterface
{
    public function handle(int $location_id, Collection $tracings): ?RefreshToken
    {
        $character_ids_to_ignore = $tracings->pluck('character_id');

        return Contract::query()
            ->where(fn ($query) => $query
                ->where('start_location_id', $location_id)
                ->orWhere('end_location_id', $location_id)
            )
            ->where(fn ($query) => $query
                ->whereHas('issuer_character.refresh_token')
                ->orWhereHas('assignee_character.refresh_token')
            )
            ->where(fn ($query) => $query
                ->whereNotIn('issuer_id', $character_ids_to_ignore)
                ->orWhereNotIn('assignee_id', $character_ids_to_ignore)
            )
            ->inRandomOrder()
            ->get()
            ->map(fn ($contract) => [
                $contract->issuer_character?->refresh_token,
                $contract->assignee_character?->refresh_token,
            ])
            ->flatten()
            ->unique()
            ->filter()
            ->filter(fn ($refresh_token) => $refresh_token->hasScope('esi-universe.read_structures.v1'))
            ->first();
    }
}
