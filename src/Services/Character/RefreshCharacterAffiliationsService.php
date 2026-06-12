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

        $characterIds = [
            ...$this->getIdsToUpdateFromDatabase(),
            ...$this->getIdsToUpdateFromCache(),
            ...$this->getMissingIdsFromCharacterInfo(),
        ];

        $this->processAffiliations($characterIds);
    }

    private function getMissingIdsFromCharacterInfo(): array
    {
        return CharacterInfo::query()
            ->whereDoesntHave('characterAffiliation')
            ->pluck('character_id')
            ->toArray();
    }

    private function processAffiliations(array $ids): void
    {
        $uniqueIds = array_unique($ids);
        $chunks = array_chunk($uniqueIds, 1000);

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
