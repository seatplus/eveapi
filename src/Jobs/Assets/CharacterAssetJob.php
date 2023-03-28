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

namespace Seatplus\Eveapi\Jobs\Assets;

use Illuminate\Support\Collection;
use Seatplus\Eveapi\Esi\HasPathValuesInterface;
use Seatplus\Eveapi\Esi\HasRequiredScopeInterface;
use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Jobs\Middleware\HasRequiredScopeMiddleware;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Traits\HasPathValues;
use Seatplus\Eveapi\Traits\HasRequiredScopes;

class CharacterAssetJob extends EsiBase implements HasPathValuesInterface, HasRequiredScopeInterface
{
    use HasPathValues;
    use HasRequiredScopes;

    private Collection $known_assets;

    private int $page = 1;

    public function __construct(
        public int $character_id
    ) {
        parent::__construct(
            method: 'get',
            endpoint: '/characters/{character_id}/assets/',
            version: 'v5',
        );

        $this->setRequiredScope('esi-assets.read_assets.v1');

        $this->setPathValues([
            'character_id' => $character_id,
        ]);

        $this->known_assets = collect();
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware(): array
    {
        return [
            new HasRequiredScopeMiddleware,
            ...parent::middleware(),
        ];
    }

    public function tags(): array
    {
        return [
            'character',
            'character_id: ' . $this->character_id,
            'assets',
        ];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function executeJob(): void
    {
        while (true) {
            $response = $this->retrieve($this->page);

            if ($response->isCachedLoad()) {
                return;
            }

            // First update the
            collect($response)
                ->each(
                    fn ($asset) => $this->known_assets->push([
                        'item_id' => $asset->item_id,
                        'assetable_id' => $this->character_id,
                        'assetable_type' => CharacterInfo::class,
                        'is_blueprint_copy' => optional($asset)->is_blueprint_copy ?? false,
                        'is_singleton' => $asset->is_singleton,
                        'location_flag' => $asset->location_flag,
                        'location_id' => $asset->location_id,
                        'location_type' => $asset->location_type,
                        'quantity' => $asset->quantity,
                        'type_id' => $asset->type_id,
                    ])
                );

            // Lastly if more pages are present load next page
            if ($this->page >= $response->pages) {
                break;
            }

            $this->page++;
        }

        $this->persist();

        // Cleanup old items
        $this->cleanup();

        // Dispatch follow-up jobs
        $this->dispatchFollowUpJobs();

        // see https://divinglaravel.com/avoiding-memory-leaks-when-running-laravel-queue-workers
        // This job is very memory consuming hence avoiding memory leaks, the worker should restart
        app('queue.worker')->shouldQuit = 1;
    }

    private function cleanup()
    {
        Asset::query()
            ->where('assetable_id', $this->character_id)
            ->whereNotIn('item_id', $this->known_assets->pluck('item_id')->toArray())
            ->delete();
    }

    private function persist()
    {
        Asset::upsert(
            $this->known_assets->toArray(),
            ['item_id'],
            ['assetable_id', 'assetable_type', 'is_blueprint_copy', 'is_singleton', 'location_flag', 'location_id', 'location_type', 'quantity', 'type_id']
        );
    }

    private function dispatchFollowUpJobs()
    {
        // Resolve unknown locations
        $this->resolveUnknownLocations();

        // Resolve unknown types
        $this->resolveUnknownTypes();
    }

    private function resolveUnknownLocations()
    {
        $unknown_location_ids = Asset::query()
            ->where('assetable_id', $this->character_id)
            ->doesntHave('location')
            ->pluck('location_id')
            ->unique();

        // if there are no unknown locations, we can skip this step
        if ($unknown_location_ids->isEmpty()) {
            return;
        }

        $refresh_token = RefreshToken::find($this->character_id);

        $unknown_location_ids->each(
            fn ($location_id) => ResolveLocationJob::dispatch($location_id, $refresh_token)
            ->onQueue('high')
        );
    }

    private function resolveUnknownTypes()
    {
        $unknown_type_ids = Asset::query()
            ->where('assetable_id', $this->character_id)
            ->doesntHave('type')
            ->pluck('type_id')
            ->unique();

        // if there are no unknown types, we can skip this step
        if ($unknown_type_ids->isEmpty()) {
            return;
        }

        $refresh_token = RefreshToken::find($this->character_id);

        $unknown_type_ids->each(
            fn ($type_id) => ResolveUniverseTypeByIdJob::dispatch($type_id, $refresh_token)
            ->onQueue('high')
        );
    }
}
