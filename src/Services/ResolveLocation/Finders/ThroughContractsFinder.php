<?php

namespace Seatplus\Eveapi\Services\ResolveLocation\Finders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Seatplus\Eveapi\Models\Contracts\Contract;
use Seatplus\Eveapi\Models\RefreshToken;

class ThroughContractsFinder implements FinderInterface
{
    public function handle(int $location_id, Collection $tracings): ?RefreshToken
    {
        $character_ids_to_ignore = $tracings->pluck('character_id');

        return Contract::query()
            ->where(fn (Builder $query) => $query
                ->where('start_location_id', $location_id)
                ->orWhere('end_location_id', $location_id)
            )
            ->where(fn (Builder $query) => $query
                ->whereHas('issuer_character.refresh_token')
                ->orWhereHas('assignee_character.refresh_token')
            )
            ->where(fn (Builder $query) => $query // @phpstan-ignore-line
                ->whereNotIn('issuer_id', $character_ids_to_ignore)
                ->orWhereNotIn('assignee_id', $character_ids_to_ignore)
            )
            ->inRandomOrder()
            ->get()
            ->map(fn (Contract $contract) => [
                $contract->issuer_character?->refresh_token,
                $contract->assignee_character?->refresh_token,
            ])
            ->flatten()
            ->unique()
            ->filter()
            ->filter(fn (RefreshToken $refresh_token) => $refresh_token->hasScope('esi-universe.read_structures.v1'))
            ->first();
    }
}
