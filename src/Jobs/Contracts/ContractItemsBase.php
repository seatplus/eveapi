<?php

namespace Seatplus\Eveapi\Jobs\Contracts;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\EsiResult;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Models\Contracts\ContractItem;

abstract class ContractItemsBase extends EsiJob implements ShouldBeUnique
{
    public int $contract_id;

    abstract protected function fetchItems(EsiClient $esi): EsiResult;

    #[\Override]
    abstract public function tags(): array;

    #[\Override]
    public function executeJob(EsiClient $esi): void
    {
        if ($this->batching() && $this->batch()->cancelled()) {
            return;
        }

        $response = $this->fetchItems($esi);
        if ($response->isCachedLoad) {
            return;
        }

        $contractItems = collect($response->data)->map(fn (object $item) => [
            'record_id' => $item->record_id,
            'contract_id' => $this->contract_id,
            'is_included' => $item->is_included,
            'is_singleton' => $item->is_singleton,
            'quantity' => $item->quantity,
            'type_id' => $item->type_id,
            'raw_quantity' => $item->raw_quantity,
        ]);

        ContractItem::upsert($contractItems->toArray(), ['record_id']);

        ContractItem::doesntHave('type')
            ->pluck('type_id')
            ->unique()
            ->each(fn (int $typeId) => ResolveUniverseTypeByIdJob::dispatch($typeId)->onQueue('high'));
    }
}
