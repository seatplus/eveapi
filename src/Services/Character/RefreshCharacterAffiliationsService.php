<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Services\Character;

use Seatplus\Eveapi\Jobs\Character\CharacterAffiliationJob;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Services\Jobs\CacheCharacterAffiliationIdsService;

class RefreshCharacterAffiliationsService
{
    public function __invoke(): void
    {

        $character_ids = [
            ...$this->getIdsToUpdateFromDatabase(),
            ...$this->getIdsToUpdateFromCache(),
            ...$this->getMissingIdsFromCharacterInfo(),
        ];

        $this->processAffiliations($character_ids);
    }

    private function getMissingIdsFromCharacterInfo(): array
    {
        return CharacterInfo::query()
            ->whereDoesntHave('character_affiliation')
            ->pluck('character_id')
            ->toArray();
    }

    private function processAffiliations(array $ids): void
    {
        $unique_ids = array_unique($ids);
        $chunks = array_chunk($unique_ids, 1000);

        foreach ($chunks as $chunk) {
            CharacterAffiliationJob::dispatch($chunk)->onQueue('high');
        }
    }

    private function getIdsToUpdateFromDatabase(): array
    {
        return CharacterAffiliation::query()
            // only those who were not pulled within the last hour
            ->where('last_pulled', '<=', now()->subHour()->toDateTimeString())
            // and don't try doomheimed characters
            ->where('corporation_id', '<>', 1_000_001)
            ->pluck('character_id')
            ->toArray();
    }

    private function getIdsToUpdateFromCache(): array
    {
        return CacheCharacterAffiliationIdsService::make()
            ->retrieve()
            ->unique()
            ->toArray();
    }
}
