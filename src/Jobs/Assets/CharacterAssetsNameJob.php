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

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Esi\HasPathValuesInterface;
use Seatplus\Eveapi\Esi\HasRequestBodyInterface;
use Seatplus\Eveapi\Esi\HasRequiredScopeInterface;
use Seatplus\Eveapi\Jobs\Middleware\EsiAvailabilityMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\HasRefreshTokenMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\HasRequiredScopeMiddleware;
use Seatplus\Eveapi\Jobs\NewEsiBase;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Traits\HasPathValues;
use Seatplus\Eveapi\Traits\HasRequestBody;
use Seatplus\Eveapi\Traits\HasRequiredScopes;

class CharacterAssetsNameJob extends NewEsiBase implements HasPathValuesInterface, HasRequestBodyInterface, HasRequiredScopeInterface
{
    use HasRequiredScopes, HasPathValues, HasRequestBody;

    public function __construct(?JobContainer $job_container = null)
    {
        $this->setJobType('character');
        parent::__construct($job_container);

        $this->setMethod('post');
        $this->setEndpoint('/characters/{character_id}/assets/names/');
        $this->setVersion('v1');

        $this->setRequiredScope('esi-assets.read_assets.v1');

        $this->setPathValues([
            'character_id' => $this->refresh_token->character_id,
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
            new EsiAvailabilityMiddleware,
            new HasRequiredScopeMiddleware,
            (new ThrottlesExceptionsWithRedis(80, 5))
                ->by($this->uniqueId())
                ->when(fn () => ! $this->isEsiRateLimited())
                ->backoff(5),
        ];
    }

    public function tags(): array
    {
        return [
            'character',
            'character_id: ' . $this->character_id,
            'assets',
            'name',
        ];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        Asset::whereHas('type.group', function (Builder $query) {
            // Only Celestials, Ships, Deployable, Starbases, Orbitals and Structures might be named
            $query->whereIn('category_id', [2, 6, 22, 23, 46, 65]);
        })->where('assetable_id', $this->refresh_token->character_id)
            ->select('item_id')
            ->where('is_singleton', true)
            ->pluck('item_id')
            ->filter(fn ($item_id) => is_null(cache()->store('file')->get($item_id)))
            ->chunk(1000)->each(function ($item_ids) {
                $this->setRequestBody($item_ids->flatten()->toArray());

                $responses = $this->retrieve();

                if ($responses->isCachedLoad()) {
                    return;
                }

                collect($responses)->each(function ($response) {

                    // "None" seems to indicate that no name is set.
                    if ($response->name === 'None') {
                        return;
                    }

                    //cache items for 1 hrs
                    cache()->store('file')->put($response->item_id, $response->name, 3600);

                    Asset::where('assetable_id', $this->refresh_token->character_id)
                        ->where('item_id', $response->item_id)
                        ->update(['name' => $response->name]);
                });
            });
    }
}
