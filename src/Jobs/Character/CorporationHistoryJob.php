<?php

namespace Seatplus\Eveapi\Jobs\Character;

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Models\Character\CorporationHistory;

class CorporationHistoryJob extends EsiJob
{
    public function __construct(public int $character_id) {}

    #[\Override]
    public function tags(): array
    {
        return ['character', 'info', "character_id:{$this->character_id}", 'corporationhistory'];
    }

    #[\Override]
    protected function executeJob(EsiClient $esi): void
    {
        $response = $esi->characters()->getCharactersCharacterIdCorporationhistory($this->character_id);
        if ($response->isCachedLoad) {
            return;
        }

        $results = collect($response->data)->map(fn (object $record) => [
            'record_id' => $record->record_id,
            'character_id' => $this->character_id,
            'corporation_id' => $record->corporation_id,
            'is_deleted' => $record->is_deleted ?? false,
            'start_date' => carbon($record->start_date),
        ]);

        CorporationHistory::query()->upsert(
            $results->toArray(),
            ['record_id', 'character_id', 'corporation_id'],
            ['is_deleted']
        );
    }
}
