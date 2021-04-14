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

namespace Seatplus\Eveapi\Jobs\Contracts;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Esi\HasPathValuesInterface;
use Seatplus\Eveapi\Esi\HasRequiredScopeInterface;
use Seatplus\Eveapi\Jobs\Middleware\HasRefreshTokenMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\HasRequiredScopeMiddleware;
use Seatplus\Eveapi\Jobs\NewEsiBase;
use Seatplus\Eveapi\Models\Contracts\ContractItem;

class ContractItemsJob extends NewEsiBase implements HasPathValuesInterface, HasRequiredScopeInterface, ShouldBeUnique
{
    public array $path_values;

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public int $uniqueFor = 3600;

    /**
     * The unique ID of the job.
     *
     * @return string
     */
    public function uniqueId()
    {
        return $this->type === 'character' ? $this->getCharacterId() : $this->getCorporationId();
    }

    public function __construct(
        public int $contract_id,
        public JobContainer $job_container,
        public string $type = 'character')
    {
        parent::__construct($job_container);

        $path_values = $this->type === 'character'
            ? ['character_id' => $job_container->getCharacterId()]
            : ['corporation_id' => $job_container->getCorporationId()];

        $this->setPathValues(array_merge($path_values, ['contract_id' => $this->contract_id]));
    }

    public function middleware(): array
    {
        return [
            new HasRefreshTokenMiddleware,
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
            'contract:' . $this->contract_id,
            'items',
            'contract_items',
        ];
    }

    public function handle(): void
    {
        if ($this->batching() && $this->batch()->cancelled()) {
            // Determine if the batch has been cancelled...

            return;
        }

        $response = $this->retrieve();

        if ($response->isCachedLoad() && ContractItem::where('contract_id', $this->contract_id)->count() > 0) {
            return;
        }

        collect($response)->each(fn ($item) => ContractItem::updateOrCreate([
            'record_id' => $item->record_id,
        ], [
            'contract_id' => $this->contract_id,
            'is_included' => $item->is_included,
            'is_singleton' =>$item->is_singleton,
            'quantity' => $item->quantity,
            'type_id' => $item->type_id,

            // optionals
            'raw_quantity' => optional($item)->raw_quantity,
        ]));
    }

    public function getMethod(): string
    {
        return 'get';
    }

    public function getEndpoint(): string
    {
        return $this->type === 'character'
            ? '/characters/{character_id}/contracts/{contract_id}/items/'
            : '/corporations/{corporation_id}/contracts/{contract_id}/items/';
    }

    public function getVersion(): string
    {
        return 'v1';
    }

    public function getPathValues(): array
    {
        return $this->path_values;
    }

    public function setPathValues(array $array): void
    {
        $this->path_values = $array;
    }

    public function getRequiredScope(): string
    {
        $key = sprintf('eveapi.scopes.%s.contracts', $this->type);

        return head(config($key));
    }

    public function getJobType(): string
    {
        return $this->type;
    }
}
