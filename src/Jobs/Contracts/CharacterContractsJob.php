<?php

namespace Seatplus\Eveapi\Jobs\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\Resources\Contracts\GetCharactersCharacterIdContracts;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Contracts\Contract;
use Seatplus\Eveapi\Models\RefreshToken;

final class CharacterContractsJob extends EsiJob
{
    protected const string OPERATION_CLASS = GetCharactersCharacterIdContracts::class;

    public function __construct(public int $character_id) {}

    #[\Override]
    public function getRefreshToken(): RefreshToken
    {
        return RefreshToken::findOrFail($this->character_id);
    }

    #[\Override]
    public function tags(): array
    {
        return ['character', "character_id:{$this->character_id}", 'contracts'];
    }

    #[\Override]
    protected function executeJob(EsiClient $esi): void
    {
        $contracts = collect();
        $page = 1;
        do {
            $response = self::OPERATION_CLASS::execute($esi, $this->character_id, $page);
            if ($response->isCachedLoad) {
                return;
            }

            foreach ($response->data as $item) {
                $contracts->push([
                    'contract_id' => $item->contract_id,
                    'acceptor_id' => $item->acceptor_id,
                    'assignee_id' => $item->assignee_id,
                    'availability' => $item->availability,
                    'date_expired' => carbon($item->date_expired),
                    'date_issued' => carbon($item->date_issued),
                    'for_corporation' => $item->for_corporation,
                    'issuer_corporation_id' => $item->issuer_corporation_id,
                    'issuer_id' => $item->issuer_id,
                    'status' => $item->status,
                    'type' => $item->type,
                    'buyout' => $item->buyout ?? null,
                    'collateral' => $item->collateral ?? null,
                    'date_accepted' => isset($item->date_accepted) ? carbon($item->date_accepted) : null,
                    'date_completed' => isset($item->date_completed) ? carbon($item->date_completed) : null,
                    'days_to_complete' => $item->days_to_complete ?? null,
                    'price' => $item->price ?? null,
                    'reward' => $item->reward ?? null,
                    'end_location_id' => $item->end_location_id ?? null,
                    'start_location_id' => $item->start_location_id ?? null,
                    'title' => $item->title ?? null,
                    'volume' => $item->volume ?? null,
                ]);
            }
            $page++;
        } while ($page <= $response->pages);

        $this->persist($contracts);
        $this->dispatchFollowUpJobs($contracts);
    }

    private function persist(Collection $contracts): void
    {
        Contract::upsert(
            $contracts->toArray(),
            ['contract_id'],
            [
                'acceptor_id', 'assignee_id', 'availability', 'date_expired', 'date_issued',
                'for_corporation', 'issuer_corporation_id', 'issuer_id', 'status', 'type',
                'buyout', 'collateral', 'date_accepted', 'date_completed', 'days_to_complete',
                'price', 'reward', 'end_location_id', 'start_location_id', 'title', 'volume',
            ]
        );

        $character = CharacterInfo::find($this->character_id);
        $contract_ids = $contracts->pluck('contract_id')->toArray();

        if ($character) {
            $character->contracts()->syncWithoutDetaching($contract_ids);
        }
    }

    private function dispatchFollowUpJobs(Collection $contracts): void
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
            ->map(fn (Contract $contract) => new CharacterContractItemsJob($this->character_id, $contract->contract_id));
    }

    private function getLocationJobs(array $contract_ids): Collection
    {
        $refresh_token = RefreshToken::find($this->character_id);

        return Contract::query()
            ->whereIn('contract_id', $contract_ids)
            ->where(fn (Builder $query) => $query->whereNotNull('start_location_id')->orWhereNotNull('end_location_id'))
            ->where(fn (Builder $query) => $query->doesntHave('start_location')->orDoesntHave('end_location'))
            ->select('start_location_id', 'end_location_id')
            ->get()
            ->map(fn (Contract $contract) => [$contract->start_location_id, $contract->end_location_id])
            ->flatten()
            ->unique()
            ->map(fn (int $location_id) => new ResolveLocationJob($location_id, $refresh_token));
    }
}
