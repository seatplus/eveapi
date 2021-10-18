<?php

/*
 * MIT License
 *
 * Copyright (c) 2019, 2020, 2021 Felix Huber
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Seatplus\Eveapi\Jobs\Wallet;

use Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Esi\HasPathValuesInterface;
use Seatplus\Eveapi\Esi\HasQueryStringInterface;
use Seatplus\Eveapi\Esi\HasRequiredScopeInterface;
use Seatplus\Eveapi\Jobs\Middleware\HasRefreshTokenMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\HasRequiredScopeMiddleware;
use Seatplus\Eveapi\Jobs\NewEsiBase;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Wallet\WalletTransaction;
use Seatplus\Eveapi\Services\Wallet\ProcessWalletTransactionResponse;
use Seatplus\Eveapi\Traits\HasPathValues;
use Seatplus\Eveapi\Traits\HasQueryValues;
use Seatplus\Eveapi\Traits\HasRequiredScopes;

class CharacterWalletTransactionJob extends NewEsiBase implements HasPathValuesInterface, HasRequiredScopeInterface, HasQueryStringInterface
{
    use HasPathValues;
    use HasRequiredScopes;
    use HasQueryValues;

    private int $from_id = PHP_INT_MAX;

    public function __construct(JobContainer $job_container)
    {
        $this->setJobType('character');
        parent::__construct($job_container);

        $this->setMethod('get');
        $this->setEndpoint('/characters/{character_id}/wallet/transactions/');
        $this->setVersion('v1');

        $this->setRequiredScope('esi-wallet.read_character_wallet.v1');

        $this->setPathValues([
            'character_id' => $this->getCharacterId(),
        ]);
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware(): array
    {
        return [
            new HasRefreshTokenMiddleware,
            new HasRequiredScopeMiddleware,
            (new ThrottlesExceptionsWithRedis(80, 5))
                ->by('esiratelimit')
                ->backoff(5),
        ];
    }

    public function tags(): array
    {
        return [
            'character',
            'character_id: ' . $this->character_id,
            'wallet',
            'transaction',
        ];
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle(): void
    {
        $processor = new ProcessWalletTransactionResponse(
            $this->getCharacterId(),
            CharacterInfo::class
        );

        $latest_transaction = WalletTransaction::where('wallet_transactionable_id', $this->getCharacterId())
            ->latest()->first();

        if ($latest_transaction) {
            $this->from_id = $latest_transaction->transaction_id - 1;
        }

        while (true) {
            $this->setQueryString([
                'from_id' => $this->from_id,
            ]);

            $response = $this->retrieve();

            if ($response->isCachedLoad() && ! is_null($latest_transaction)) {
                return;
            }

            // If no more transactions are present, break the loop.
            if (collect($response)->isEmpty()) {
                break;
            }

            $last_transaction_id = $processor->execute($response);

            $this->from_id = $last_transaction_id - 1;
        }

        // see https://divinglaravel.com/avoiding-memory-leaks-when-running-laravel-queue-workers
        // This job is very memory consuming hence avoiding memory leaks, the worker should restart
        app('queue.worker')->shouldQuit  = 1;
    }
}
