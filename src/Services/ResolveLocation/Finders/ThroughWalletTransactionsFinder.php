<?php

declare(strict_types=1);

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
    #[\Override]
    public function handle(int $locationId, Collection $tracings): ?RefreshToken
    {
        $characterIdsToIgnore = $tracings->pluck('character_id');

        $refreshToken = WalletTransaction::query()
            ->where('location_id', $locationId)
            ->whereNotIn('wallet_transactionable_id', $characterIdsToIgnore)
            ->whereHasMorph(
                'walletTransactionable',
                CharacterInfo::class,
                fn (Builder $query) => $query->whereHas('refreshToken')
            )
            ->where('wallet_transactionable_type', CharacterInfo::class)
            ->inRandomOrder()
            ->get()
            ->map(fn (WalletTransaction $walletTransaction) => data_get($walletTransaction, 'walletTransactionable.refreshToken'))
            ->unique()
            // filter refresh token that has scope esi-universe.read_structures.v1
            ->filter(fn (RefreshToken $refreshToken) => $refreshToken->hasScope('esi-universe.read_structures.v1'))
            ->first();

        if ($refreshToken) {
            return $refreshToken;
        }

        return WalletTransaction::query()
            ->where('location_id', $locationId)
            ->where('wallet_transactionable_type', CorporationInfo::class)
            ->inRandomOrder()
            ->pluck('wallet_transactionable_id')
            ->unique()
            ->map(function (int $corporationId) {

                $service = new FindCorporationRefreshToken;

                return $service($corporationId, 'esi-universe.read_structures.v1', 'Director');
            })
            ->filter()
            ->first();

    }
}
