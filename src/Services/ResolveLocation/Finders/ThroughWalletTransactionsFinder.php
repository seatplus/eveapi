<?php

namespace Seatplus\Eveapi\Services\ResolveLocation\Finders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Wallet\WalletTransaction;
use Seatplus\Eveapi\Services\FindCorporationRefreshToken;

class ThroughWalletTransactionsFinder implements FinderInterface
{
    public function handle(int $location_id, Collection $tracings): ?RefreshToken
    {
        $character_ids_to_ignore = $tracings->pluck('character_id');

        $refresh_token = WalletTransaction::query()
            ->where('location_id', $location_id)
            ->whereNotIn('wallet_transactionable_id', $character_ids_to_ignore)
            ->whereHasMorph(
                'wallet_transactionable',
                CharacterInfo::class,
                fn (Builder $query) => $query->whereHas('refresh_token')
            )
            ->where('wallet_transactionable_type', CharacterInfo::class)
            ->inRandomOrder()
            ->get()
            ->map(fn (WalletTransaction $wallet_transaction) => $wallet_transaction->wallet_transactionable->refresh_token)
            ->unique()
            // filter refresh token that has scope esi-universe.read_structures.v1
            ->filter(fn (RefreshToken $refresh_token) => $refresh_token->hasScope('esi-universe.read_structures.v1'))
            ->first();

        if ($refresh_token) {
            return $refresh_token;
        }

        return WalletTransaction::query()
            ->where('location_id', $location_id)
            ->where('wallet_transactionable_type', CorporationInfo::class)
            ->inRandomOrder()
            ->pluck('wallet_transactionable_id')
            ->unique()
            ->map(function (int $corporation_id) {

                $service = new FindCorporationRefreshToken;

                return $service($corporation_id, 'esi-universe.read_structures.v1', 'Director');
            })
            ->filter()
            ->first();

    }
}
