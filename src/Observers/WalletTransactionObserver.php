<?php


namespace Seatplus\Eveapi\Observers;


use Seatplus\Eveapi\Jobs\Seatplus\ResolveUniverseTypesByTypeIdJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Wallet\WalletTransaction;
use Seatplus\Eveapi\Services\FindCorporationRefreshToken;

class WalletTransactionObserver
{

    private WalletTransaction $wallet_transaction;

    public function created(WalletTransaction $wallet_transaction)
    {
        $this->wallet_transaction = $wallet_transaction;

        $this->handleTypes();
        $this->handleLocations();
    }

    private function handleTypes()
    {
        if ($this->wallet_transaction->type) {
            return;
        }

        ResolveUniverseTypesByTypeIdJob::dispatch($this->wallet_transaction->type_id)->onQueue('high');
    }

    private function handleLocations()
    {
        if ($this->wallet_transaction->location) {
            return;
        }

        if($this->wallet_transaction->wallet_transactionable_type === CharacterInfo::class) {

            $refresh_token = RefreshToken::find($this->wallet_transaction->wallet_transactionable_id);
        }

        if($this->wallet_transaction->wallet_transactionable_type === CorporationInfo::class) {

            $find_corporation_refresh_token = new FindCorporationRefreshToken;

            $refresh_token = $find_corporation_refresh_token($this->wallet_transaction->wallet_transactionable_id, 'esi-universe.read_structures.v1', 'Director') ?? $this->getRandomRefreshToken();
        }

        dispatch(new ResolveLocationJob($this->wallet_transaction->location_id, $refresh_token))->onQueue('high');

    }

    private function getRandomRefreshToken(){

        $random_character = $this->wallet_transaction->wallet_transactionable->characters->random();

        return RefreshToken::find($random_character->character_id);
    }
}
