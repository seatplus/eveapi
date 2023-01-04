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

use Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis;
use Illuminate\Support\Collection;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Esi\HasPathValuesInterface;
use Seatplus\Eveapi\Esi\HasRequiredScopeInterface;
use Seatplus\Eveapi\Jobs\Middleware\HasRefreshTokenMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\HasRequiredScopeMiddleware;
use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Traits\HasPathValues;
use Seatplus\Eveapi\Traits\HasRequiredScopes;

class CharacterAssetJob extends EsiBase implements HasPathValuesInterface, HasRequiredScopeInterface
{
    use HasPathValues;
    use HasRequiredScopes;

    private Collection $known_assets;

    private int $page = 1;

    public function __construct(
        public JobContainer $job_container
    ) {
        $this->setJobType('character');
        parent::__construct($job_container);

        $this->setMethod('get');
        $this->setEndpoint('/characters/{character_id}/assets/');
        $this->setVersion('v5');

        $this->setPathValues([
            'character_id' => $job_container->getCharacterId(),
        ]);

        $this->setRequiredScope('esi-assets.read_assets.v1');

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
            collect($response)->each(fn ($asset) => Asset::updateOrCreate([
                'item_id' => $asset->item_id,
            ], [
                'assetable_id' => $this->refresh_token->character_id,
                'assetable_type' => CharacterInfo::class,
                'is_blueprint_copy' => optional($asset)->is_blueprint_copy ?? false,
                'is_singleton' => $asset->is_singleton,
                'location_flag' => $asset->location_flag,
                'location_id' => $asset->location_id,
                'location_type' => $asset->location_type,
                'quantity' => $asset->quantity,
                'type_id' => $asset->type_id,
            ]))->pipe(fn (Collection $response) => $response->pluck('item_id')->each(fn ($id) => $this->known_assets->push($id)));

            // Lastly if more pages are present load next page
            if ($this->page >= $response->pages) {
                break;
            }

            $this->page++;
        }

        // Cleanup old items
        $this->cleanup();

        // see https://divinglaravel.com/avoiding-memory-leaks-when-running-laravel-queue-workers
        // This job is very memory consuming hence avoiding memory leaks, the worker should restart
        app('queue.worker')->shouldQuit = 1;
    }

    private function cleanup()
    {
        Asset::query()
            ->where('assetable_id', $this->getCharacterId())
            ->whereNotIn('item_id', $this->known_assets->toArray())
            ->delete();
    }
}
