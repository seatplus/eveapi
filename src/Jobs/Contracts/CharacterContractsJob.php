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
use Seatplus\Eveapi\Actions\HasPathValuesInterface;
use Seatplus\Eveapi\Actions\HasRequiredScopeInterface;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Middleware\EsiAvailabilityMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\EsiRateLimitedMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\HasRefreshTokenMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\HasRequiredScopeMiddleware;
use Seatplus\Eveapi\Jobs\NewEsiBase;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Contracts\Contract;

class CharacterContractsJob extends NewEsiBase implements HasPathValuesInterface, HasRequiredScopeInterface, ShouldBeUnique
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
        return sprintf('contract_job:%s', $this->getCharacterId());
    }

    public function __construct(
        public JobContainer $job_container
    ) {
        parent::__construct($job_container);

        $this->setPathValues([
            'character_id' => $job_container->getCharacterId(),
        ]);
    }

    public function middleware(): array
    {
        return [
            new HasRefreshTokenMiddleware,
            new HasRequiredScopeMiddleware,
            new EsiRateLimitedMiddleware,
            new EsiAvailabilityMiddleware,
        ];
    }

    public function tags(): array
    {
        return [
            'character',
            'character_id: ' . $this->getCharacterId(),
            'contracts',
        ];
    }

    public function handle(): void
    {
        if ($this->batching() && $this->batch()->cancelled()) {
            // Determine if the batch has been cancelled...

            return;
        }

        $page = 1;

        while (true) {
            $response = $this->retrieve($page);

            if ($response->isCachedLoad()) {
                return;
            }

            collect($response)->each(fn ($contract) => Contract::updateOrCreate([
                'contract_id' => $contract->contract_id,
            ], [
                'acceptor_id' => $contract->acceptor_id,
                'assignee_id' => $contract->assignee_id,
                'availability' => $contract->availability,
                'date_expired' => carbon($contract->date_expired),
                'date_issued' => carbon($contract->date_issued),
                'for_corporation' => $contract->for_corporation,
                'issuer_corporation_id' => $contract->issuer_corporation_id,
                'issuer_id' => $contract->issuer_id,
                'status' => $contract->status,
                'type' => $contract->type,

                //optionals
                'buyout' => optional($contract)->buyout,
                'collateral' => optional($contract)->collateral,
                'date_accepted' => optional($contract)->date_accepted ? carbon(optional($contract)->date_accepted) : null,
                'date_completed' => optional($contract)->date_completed ? carbon(optional($contract)->date_completed) : null,
                'days_to_complete' => optional($contract)->days_to_complete,
                'price' => optional($contract)->price,
                'reward' => optional($contract)->reward,
                'end_location_id' => optional($contract)->end_location_id,
                'start_location_id' => optional($contract)->start_location_id,
                'title' => optional($contract)->title,
                'volume' => optional($contract)->volume,
            ]));

            $contract_ids = collect($response)->pluck('contract_id')->toArray();

            $character = CharacterInfo::find($this->job_container->getCharacterId());

            if ($character) {
                $character->contracts()->syncWithoutDetaching($contract_ids);
            }

            $location_job_array = Contract::query()
                ->whereIn('contract_id', $contract_ids)
                ->doesntHave('items')
                ->where('volume', '>', 0)
                ->where('status', '<>', 'deleted')
                ->where('type', '<>', 'courier')
                ->get()
                ->map(fn ($contract) => new ContractItemsJob($contract->contract_id, $this->job_container, 'character'));

            if ($this->batching()) {
                $this->batch()->add($location_job_array);
            }

            // Lastly if more pages are present load next page
            if ($page >= $response->pages) {
                break;
            }

            $page++;
        }
    }

    public function getMethod(): string
    {
        return 'get';
    }

    public function getEndpoint(): string
    {
        return '/characters/{character_id}/contracts/';
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
        return head(config('eveapi.scopes.character.contracts'));
    }
}
