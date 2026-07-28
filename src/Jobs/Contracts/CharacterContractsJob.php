<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Jobs\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

    public function __construct(public int $characterId) {}

    #[\Override]
    protected function wrapExecuteJobInTransaction(): bool
    {
        return false;
    }

    #[\Override]
    public function getRefreshToken(): RefreshToken
    {
        return RefreshToken::findOrFail($this->characterId);
    }

    #[\Override]
    public function tags(): array
    {
        return ['character', "character_id:{$this->characterId}", 'contracts'];
    }

    #[\Override]
    public function executeJob(EsiClient $esi): void
    {
        $contracts = collect();
        $page = 1;
        do {
            $response = self::OPERATION_CLASS::execute($esi, $this->characterId, $page);
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
        // Paging ran outside any transaction; wrap only the write. The upsert and the pivot sync
        // stay atomic together as they were under the old whole-job transaction.
        DB::transaction(function () use ($contracts): void {
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

            $character = CharacterInfo::find($this->characterId);
            $contractIds = $contracts->pluck('contract_id')->toArray();

            if ($character) {
                $character->contracts()->syncWithoutDetaching($contractIds);
            }
        });
    }

    private function dispatchFollowUpJobs(Collection $contracts): void
    {
        $contractIds = $contracts->pluck('contract_id')->toArray();

        $contractItemJobs = $this->getContractItemJobs($contractIds);
        $locationJobs = $this->getLocationJobs($contractIds);

        if ($this->batching()) {
            $this->batch()->add([...$contractItemJobs, ...$locationJobs]);

            return;
        }

        foreach ([...$contractItemJobs, ...$locationJobs] as $job) {
            dispatch($job)->onQueue('high');
        }
    }

    private function getContractItemJobs(array $contractIds): Collection
    {
        return Contract::query()
            ->whereIn('contract_id', $contractIds)
            ->doesntHave('items')
            ->where('volume', '>', 0)
            ->where('status', '<>', 'deleted')
            ->where('type', '<>', 'courier')
            ->get()
            ->map(fn (Contract $contract) => new CharacterContractItemsJob($this->characterId, $contract->contract_id));
    }

    private function getLocationJobs(array $contractIds): Collection
    {
        $refreshToken = RefreshToken::find($this->characterId);

        return Contract::query()
            ->whereIn('contract_id', $contractIds)
            ->where(fn (Builder $query) => $query->whereNotNull('start_location_id')->orWhereNotNull('end_location_id'))
            ->where(fn (Builder $query) => $query->doesntHave('startLocation')->orDoesntHave('endLocation'))
            ->select('start_location_id', 'end_location_id')
            ->get()
            ->map(fn (Contract $contract) => [$contract->start_location_id, $contract->end_location_id])
            ->flatten()
            ->unique()
            ->map(fn (int $locationId) => new ResolveLocationJob($locationId, $refreshToken));
    }
}
