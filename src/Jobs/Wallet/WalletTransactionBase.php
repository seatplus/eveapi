<?php

namespace Seatplus\Eveapi\Jobs\Wallet;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\EsiResult;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Models\Wallet\WalletTransaction;

abstract class WalletTransactionBase extends EsiJob
{
    protected int $from_id = PHP_INT_MAX;

    protected array $transactions = [];

    abstract protected function fetchTransactions(EsiClient $esi, ?int $fromId): EsiResult;

    abstract protected function transactionableId(): int;

    abstract protected function transactionableType(): string;

    protected function division(): ?int
    {
        return null;
    }

    #[\Override]
    public function executeJob(EsiClient $esi): void
    {
        $latest = WalletTransaction::where('wallet_transactionable_id', $this->transactionableId())->latest()->first();
        if ($latest) {
            $this->from_id = $latest->transaction_id - 1;
        }

        while (true) {
            $fromId = $this->from_id === PHP_INT_MAX ? null : $this->from_id;
            $response = $this->fetchTransactions($esi, $fromId);

            if ($response->isCachedLoad) {
                return;
            }
            if (empty($response->data)) {
                break;
            }

            $transactions = [];
            foreach ($response->data as $item) {
                $transactions[] = [
                    'transaction_id' => $item->transaction_id,
                    'wallet_transactionable_id' => $this->transactionableId(),
                    'wallet_transactionable_type' => $this->transactionableType(),
                    'division' => $this->division(),
                    'client_id' => $item->client_id,
                    'date' => carbon($item->date),
                    'is_buy' => $item->is_buy,
                    'is_personal' => $item->is_personal ?? false,
                    'journal_ref_id' => $item->journal_ref_id,
                    'location_id' => $item->location_id,
                    'quantity' => $item->quantity,
                    'type_id' => $item->type_id,
                    'unit_price' => $item->unit_price,
                ];
            }

            $lastTransactionId = end($transactions)['transaction_id'] - 1;
            if ($lastTransactionId === $this->from_id) {
                break;
            }
            $this->from_id = $lastTransactionId;
            $this->transactions = array_merge($this->transactions, $transactions);
        }

        WalletTransaction::upsert($this->transactions, ['transaction_id']);
        $this->dispatchFollowUpJobs();
        if (app()->bound('queue.worker')) {
            app('queue.worker')->shouldQuit = true;
        }
    }

    private function dispatchFollowUpJobs(): void
    {
        WalletTransaction::query()
            ->doesntHave('type')
            ->pluck('type_id')
            ->unique()
            ->each(fn (int $typeId) => ResolveUniverseTypeByIdJob::dispatch($typeId)->onQueue('high'));

        $refreshToken = $this->getRefreshToken();
        WalletTransaction::query()
            ->doesntHave('location')
            ->pluck('location_id')
            ->unique()
            ->each(fn (int $locationId) => ResolveLocationJob::dispatch($locationId, $refreshToken)->onQueue('high'));
    }
}
