<?php


namespace Seatplus\Eveapi\Jobs\Hydrate\Maintenance;


use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\Structure;
use Seatplus\Eveapi\Models\Wallet\WalletTransaction;
use Seatplus\Eveapi\Services\FindCorporationRefreshToken;

class GetMissingLocationFromWalletTransaction extends HydrateMaintenanceBase
{

    public function handle()
    {
        if ($this->batch()->cancelled()) {
            // Determine if the batch has been cancelled...

            return;
        }

        WalletTransaction::whereDoesntHave('location', fn ($query) => $query->whereHasMorph('locatable', [Structure::class, Station::class]))
            ->inRandomOrder()
            ->get()
            ->unique('location_id')
            ->each(function ($wallet_transaction) {
                $refresh_token = null;

                if ($wallet_transaction->wallet_transactionable_type === CharacterInfo::class) {
                    $refresh_token = RefreshToken::find($wallet_transaction->wallet_transactionable_id);
                }

                if ($wallet_transaction->wallet_transactionable_type === CorporationInfo::class) {
                    $find_corporation_refresh_token = new FindCorporationRefreshToken;

                    $refresh_token = $find_corporation_refresh_token($this->wallet_transaction->wallet_transactionable_id, 'esi-universe.read_structures.v1', 'Director') ?? $this->getRandomRefreshToken($wallet_transaction);
                }

                if ($refresh_token) {
                    dispatch(new ResolveLocationJob($wallet_transaction->location_id, $refresh_token))->onQueue('high');
                }
            });
    }

    private function getRandomRefreshToken(WalletTransaction $wallet_transaction)
    {
        $random_character = $wallet_transaction->wallet_transactionable->characters->random();

        return RefreshToken::find($random_character->character_id);
    }
}