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

use Illuminate\Support\Collection;
use Seatplus\Eveapi\Esi\HasPathValuesInterface;
use Seatplus\Eveapi\Esi\HasRequiredScopeInterface;
use Seatplus\Eveapi\Jobs\EsiBase;

use Seatplus\Eveapi\Jobs\Middleware\HasRequiredScopeMiddleware;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Contracts\Contract;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Traits\HasPages;
use Seatplus\Eveapi\Traits\HasPathValues;
use Seatplus\Eveapi\Traits\HasRequiredScopes;

class CharacterContractsJob extends EsiBase implements HasPathValuesInterface, HasRequiredScopeInterface
{
    use HasPathValues;
    use HasPages;
    use HasRequiredScopes;

    public function __construct(
        public int $character_id,
    ) {
        parent::__construct(
            method: 'get',
            endpoint: '/characters/{character_id}/contracts/',
            version: 'v1',
        );

        $this->setPathValues([
            'character_id' => $this->character_id,
        ]);

        $this->setRequiredScope(head(config('eveapi.scopes.character.contracts')));
    }

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
            'contracts',
        ];
    }

    public function executeJob(): void
    {
        $contracts = collect();

        while (true) {
            $response = $this->retrieve($this->getPage());

            if ($response->isCachedLoad()) {
                return;
            }

            collect($response)->each(fn ($contract) => $contracts->push([
                // primary
                'contract_id' => $contract->contract_id,
                // other columns
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

            // Lastly if more pages are present load next page
            if ($this->getPage() >= $response->pages) {
                break;
            }

            $this->incrementPage();
        }

        $this->persist($contracts);

        $this->dispatchFollowUpJobs($contracts);
    }

    private function persist(Collection $contracts)
    {
        Contract::upsert(
            $contracts->toArray(),
            ['contract_id'],
            // TODO check which columns are actually can be updated
            [
                'acceptor_id',
                'assignee_id',
                'availability',
                'date_expired',
                'date_issued',
                'for_corporation',
                'issuer_corporation_id',
                'issuer_id',
                'status',
                'type',
                // optionals
                'buyout',
                'collateral',
                'date_accepted',
                'date_completed',
                'days_to_complete',
                'price',
                'reward',
                'end_location_id',
                'start_location_id',
                'title',
                'volume',
            ]
        );

        // Next sync the contracts to the character
        $character = CharacterInfo::find($this->character_id);

        $contract_ids = $contracts->pluck('contract_id')->toArray();

        if ($character) {
            $character->contracts()->syncWithoutDetaching($contract_ids);
        }
    }

    private function dispatchFollowUpJobs(Collection $contracts)
    {
        $contract_ids = $contracts->pluck('contract_id')->toArray();

        $contract_item_jobs = $this->getContractItemJobs($contract_ids);
        $location_jobs = $this->getLocationJobs($contract_ids);


        if ($this->batching()) {
            $this->batch()->add([...$contract_item_jobs, ...$location_jobs]);

            return;
        }

        foreach ([...$contract_item_jobs, ...$location_jobs] as $job) {
            dispatch($job)->onQueue('high');
        }
    }

    private function getContractItemJobs(array $contract_ids): Collection
    {
        return Contract::query()
            ->whereIn('contract_id', $contract_ids)
            ->doesntHave('items')
            ->where('volume', '>', 0)
            ->where('status', '<>', 'deleted')
            ->where('type', '<>', 'courier')
            ->get()
            ->map(fn ($contract) => new CharacterContractItemsJob($this->character_id, $contract->contract_id));
    }

    private function getLocationJobs(array $contract_ids): Collection
    {
        $refresh_token = RefreshToken::find($this->character_id);

        return Contract::query()
            ->whereIn('contract_id', $contract_ids)
            // where has start_location_id or end_location_id
            ->where(fn ($query) => $query->whereNotNull('start_location_id')->orWhereNotNull('end_location_id'))
            // where doesn't have start_location or end_location
            ->where(fn ($query) => $query->doesntHave('start_location')->orDoesntHave('end_location'))
            ->select('start_location_id', 'end_location_id')
            ->get()
            ->map(fn ($contract) => [$contract->start_location_id, $contract->end_location_id])
            ->flatten()
            ->unique()
            ->map(fn ($location_id) => new ResolveLocationJob($location_id, $refresh_token));
    }
}
