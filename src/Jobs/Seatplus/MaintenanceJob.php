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

namespace Seatplus\Eveapi\Jobs\Seatplus;

use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingAssetsNames;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingCategorys;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingCharacterInfosFromCorporationMemberTracking;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingGroups;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingLocationFromAssets;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingLocationFromContracts;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingLocationFromCorporationMemberTracking;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingLocationFromWalletTransaction;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingTypesFromCharacterAssets;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingTypesFromContractItem;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingTypesFromCorporationMemberTracking;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingTypesFromLocations;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingTypesFromWalletTransaction;
use Seatplus\Eveapi\Jobs\Middleware\EsiAvailabilityMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\EsiRateLimitedMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\RedisFunnelMiddleware;

class MaintenanceJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    public function tags(): array
    {
        return [
            'Maintenance',
        ];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Bus::batch([
            new GetMissingTypesFromCharacterAssets,
            new GetMissingTypesFromLocations,
            new GetMissingGroups,
            new GetMissingCategorys,
            new GetMissingLocationFromAssets,
            new GetMissingAssetsNames,
            new GetMissingTypesFromCorporationMemberTracking,
            new GetMissingLocationFromCorporationMemberTracking,
            new GetMissingTypesFromWalletTransaction,
            new GetMissingLocationFromWalletTransaction,
            new GetMissingCharacterInfosFromCorporationMemberTracking,

            // TODO: Missing Affiliations from character_users, character_info and contacts

            // Contracts
            new GetMissingTypesFromContractItem,
            new GetMissingLocationFromContracts,

        ])
            ->then(fn (Batch $batch) => logger()->info('Maintenance job finished'))
            ->name('Maintenance Job')
            ->onQueue('low')
            ->allowFailures()
            ->dispatch();
    }
}
