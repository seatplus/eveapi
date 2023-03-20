<?php

namespace Seatplus\Eveapi\Jobs\Wallet;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Seatplus\Eveapi\Esi\HasPathValuesInterface;
use Seatplus\Eveapi\Esi\HasQueryStringInterface;
use Seatplus\Eveapi\Esi\HasRequiredScopeInterface;
use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Wallet\WalletTransaction;
use Seatplus\Eveapi\Traits\HasPages;
use Seatplus\Eveapi\Traits\HasPathValues;
use Seatplus\Eveapi\Traits\HasQueryValues;
use Seatplus\Eveapi\Traits\HasRequiredScopes;

abstract class WalletTransactionBase extends EsiBase implements HasPathValuesInterface, HasRequiredScopeInterface, HasQueryStringInterface
{
    use HasPathValues, HasRequiredScopes, HasQueryValues;

    protected int $from_id = PHP_INT_MAX;

    protected array $transactions = [];

    public function executeJob(): void
    {
        // get path values
        $path_values = $this->getPathValues();

        // get wallet_transactionable_type
        $wallet_transactionable_type = Arr::has($path_values, 'character_id') ? CharacterInfo::class : CorporationInfo::class;

        $wallet_transactionable_id = match ($wallet_transactionable_type) {
            CharacterInfo::class => $path_values['character_id'],
            CorporationInfo::class => $path_values['corporation_id'],
        };

        $division_id = Arr::get($path_values, 'division', null);

        $latest_transaction = WalletTransaction::where('wallet_transactionable_id', $wallet_transactionable_id)
            ->latest()->first();

        if ($latest_transaction) {
            $this->from_id = $latest_transaction->transaction_id - 1;
        }

        while (true) {

            $this->setQueryString([
                'from_id' => $this->from_id,
            ]);

            $response = $this->retrieve();

            if ($response->isCachedLoad()) {
                return;
            }

            // If no more transactions are present, break the loop.
            if (collect($response)->isEmpty()) {
                break;
            }

            $transactions = collect($response)
                ->map(fn ($entry) => [
                    'transaction_id' => $entry->transaction_id,

                    'wallet_transactionable_id' => $wallet_transactionable_id,
                    'wallet_transactionable_type' => $wallet_transactionable_type,
                    'division' => $division_id,

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
                ])->toArray();

            // get the last transaction id
            $this->from_id = Arr::last($transactions)['transaction_id'] - 1;

            $this->transactions = array_merge($this->transactions, $transactions);

        }

        WalletTransaction::upsert($this->transactions, ['transaction_id']);

        // see https://divinglaravel.com/avoiding-memory-leaks-when-running-laravel-queue-workers
        // This job is very memory consuming hence avoiding memory leaks, the worker should restart
        app('queue.worker')->shouldQuit = 1;
    }

}