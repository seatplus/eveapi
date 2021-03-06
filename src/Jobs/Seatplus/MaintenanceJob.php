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

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Seatplus\Eveapi\Jobs\Middleware\EsiAvailabilityMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\EsiRateLimitedMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\RedisFunnelMiddleware;
use Seatplus\Eveapi\Services\Maintenance\GetMissingAssetsNamesPipe;
use Seatplus\Eveapi\Services\Maintenance\GetMissingCategorysPipe;
use Seatplus\Eveapi\Services\Maintenance\GetMissingCharacterInfosFromCorporationMemberTrackingPipe;
use Seatplus\Eveapi\Services\Maintenance\GetMissingGroupsPipe;
use Seatplus\Eveapi\Services\Maintenance\GetMissingLocationFromAssetsPipe;
use Seatplus\Eveapi\Services\Maintenance\GetMissingLocationFromContractsPipe;
use Seatplus\Eveapi\Services\Maintenance\GetMissingLocationFromCorporationMemberTrackingPipe;
use Seatplus\Eveapi\Services\Maintenance\GetMissingLocationFromWalletTransactionPipe;
use Seatplus\Eveapi\Services\Maintenance\GetMissingTypesFromCharacterAssetsPipe;
use Seatplus\Eveapi\Services\Maintenance\GetMissingTypesFromContractItemPipe;
use Seatplus\Eveapi\Services\Maintenance\GetMissingTypesFromCorporationMemberTrackingPipe;
use Seatplus\Eveapi\Services\Maintenance\GetMissingTypesFromLocationsPipe;
use Seatplus\Eveapi\Services\Maintenance\GetMissingTypesFromWalletTransactionPipe;

class MaintenanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    private array $pipes = [
        GetMissingTypesFromCharacterAssetsPipe::class,
        GetMissingTypesFromLocationsPipe::class,
        GetMissingGroupsPipe::class,
        GetMissingCategorysPipe::class,
        GetMissingLocationFromAssetsPipe::class,
        GetMissingAssetsNamesPipe::class,
        GetMissingTypesFromCorporationMemberTrackingPipe::class,
        GetMissingLocationFromCorporationMemberTrackingPipe::class,
        GetMissingTypesFromWalletTransactionPipe::class,
        GetMissingLocationFromWalletTransactionPipe::class,
        GetMissingCharacterInfosFromCorporationMemberTrackingPipe::class,
        // TODO: Missing Affiliations from character_users, character_info and contacts

        // Contracts
        GetMissingTypesFromContractItemPipe::class,
        GetMissingLocationFromContractsPipe::class,
    ];

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware(): array
    {
        return [
            new EsiRateLimitedMiddleware,
            new EsiAvailabilityMiddleware,
            new RedisFunnelMiddleware,
        ];
    }

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
        app(Pipeline::class)
            ->send(null)
            ->through($this->pipes)
            ->then(fn () => logger()->info('Maintenance job finished'));
    }
}
