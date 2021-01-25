<?php


namespace Seatplus\Eveapi\Services\Wallet;


use Seat\Eseye\Containers\EsiResponse;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\Structure;
use Seatplus\Eveapi\Models\Universe\System;
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Models\Wallet\WalletJournal;
use Seatplus\Eveapi\Models\Wallet\WalletTransaction;

class ProcessWalletTransactionResponse
{

    public function __construct(
        private int $wallet_transactionable_id,
        private string $wallet_transactionable_type
    )
    {
    }

    public function execute(EsiResponse $response) : int
    {
        return collect($response)
            ->each(fn($entry) => WalletTransaction::firstOrCreate(
                [
                    'transaction_id' => $entry->transaction_id]
                ,
                [
                    'wallet_transactionable_id' => $this->wallet_transactionable_id,
                    'wallet_transactionable_type' => $this->wallet_transactionable_type,

                    // required props
                    'client_id' => $entry->client_id,
                    'date' => carbon($entry->date),
                    'is_buy' => $entry->is_buy,
                    'is_personal' => $entry->is_personal,
                    'journal_ref_id' => $entry->journal_ref_id,
                    'location_id' => $entry->location_id,
                    'quantity' => $entry->quantity,
                    'type_id' => $entry->type_id,
                    'unit_price' => $entry->unit_price,
                ]
            ))
            ->last()->transaction_id;
    }
}
