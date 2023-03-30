<?php

namespace Seatplus\Eveapi\Jobs\Wallet;

use Illuminate\Support\Arr;
use Seatplus\Eveapi\Esi\HasPathValuesInterface;
use Seatplus\Eveapi\Esi\HasQueryStringInterface;
use Seatplus\Eveapi\Esi\HasRequiredScopeInterface;
use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Wallet\WalletTransaction;
use Seatplus\Eveapi\Traits\HasPathValues;
use Seatplus\Eveapi\Traits\HasQueryValues;
use Seatplus\Eveapi\Traits\HasRequiredScopes;

abstract class WalletTransactionBase extends EsiBase implements HasPathValuesInterface, HasRequiredScopeInterface, HasQueryStringInterface
{
    use HasPathValues;
    use HasRequiredScopes;
    use HasQueryValues;

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
            $last_transaction_id = Arr::last($transactions)['transaction_id'] - 1;

            // if the last transaction id is equal to the from_id, break the loop
            if ($last_transaction_id === $this->from_id) {
                break;
            }

            // set the from_id to the last transaction id
            $this->from_id = $last_transaction_id;

            $this->transactions = array_merge($this->transactions, $transactions);
        }

        $this->persistTransactions();
        $this->dispatchFollowUpJobs();

        // see https://divinglaravel.com/avoiding-memory-leaks-when-running-laravel-queue-workers
        // This job is very memory consuming hence avoiding memory leaks, the worker should restart
        app('queue.worker')->shouldQuit = 1;
    }

    private function persistTransactions()
    {
        WalletTransaction::upsert($this->transactions, ['transaction_id']);
    }

    private function dispatchFollowUpJobs()
    {
        $this->dispatchMissingTypeJobs();
        $this->dispatchMissingLocationJobs();
    }

    private function dispatchMissingTypeJobs()
    {
        WalletTransaction::query()
            ->doesntHave('type')
            ->pluck('type_id')
            ->unique()
            ->each(fn ($type_id) => ResolveUniverseTypeByIdJob::dispatch($type_id)->onQueue('high'));
    }

    private function dispatchMissingLocationJobs()
    {
        $refresh_token = $this->getRefreshToken();

        WalletTransaction::query()
            ->doesntHave('location')
            ->pluck('location_id')
            ->unique()
            ->each(fn ($location_id) => ResolveLocationJob::dispatch($location_id, $refresh_token)->onQueue('high'));
    }
}
