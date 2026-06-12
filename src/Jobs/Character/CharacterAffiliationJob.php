<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Jobs\Character;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiClient\Exceptions\RequestFailedException;
use Seatplus\EsiSchema\Resources\Character\PostCharactersAffiliation;
use Seatplus\Eveapi\Jobs\Alliances\AllianceInfoJob;
use Seatplus\Eveapi\Jobs\Corporation\CorporationInfoJob;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;

final class CharacterAffiliationJob extends EsiJob
{
    protected const string OPERATION_CLASS = PostCharactersAffiliation::class;

    private array $manualIds = [];

    private readonly Collection $characterAffiliations;

    public function __construct(int|array $characterIds)
    {
        $this->setManualIds($characterIds);
        throw_unless(count($this->manualIds) <= 1000, new \Exception('Character ids must not exceed 1000'));
        $this->characterAffiliations = collect();
    }

    #[\Override]
    public function tags(): array
    {
        return ['character', 'affiliation'];
    }

    #[\Override]
    public function executeJob(EsiClient $esi): void
    {
        $this->updateOrCreateCharacterAffiliations($this->getManualIds(), $esi);

        CharacterAffiliation::query()->upsert(
            $this->characterAffiliations->toArray(),
            ['character_id'],
            ['corporation_id', 'alliance_id', 'faction_id', 'last_pulled']
        );

        $this->followUp();
    }

    public function getManualIds(): array
    {
        return $this->manualIds;
    }

    public function setManualIds(int|array $manualIds): void
    {
        $this->manualIds = is_array($manualIds) ? $manualIds : [$manualIds];
    }

    private function updateOrCreateCharacterAffiliations(array $characterIds, EsiClient $esi): void
    {
        $timestamp = now();
        try {
            $response = self::OPERATION_CLASS::execute($esi, $characterIds);

            if ($response->isCachedLoad) {
                return;
            }

            foreach ($response->data as $result) {
                $this->characterAffiliations->push([
                    'character_id' => $result->character_id,
                    'corporation_id' => $result->corporation_id,
                    'alliance_id' => $result->alliance_id ?? null,
                    'faction_id' => $result->faction_id ?? null,
                    'last_pulled' => $timestamp,
                ]);
            }
        } catch (RequestFailedException) {
            $this->handleFailedRequest($characterIds, $esi);
        }
    }

    private function followUp(): void
    {
        CharacterAffiliation::query()
            ->has('character')
            ->whereDoesntHave('corporation')
            ->pluck('corporation_id')
            ->unique()
            ->each(fn (int $corpId) => CorporationInfoJob::dispatch($corpId)->onQueue('high'));

        CharacterAffiliation::query()
            ->has('character')
            ->whereNotNull('alliance_id')
            ->whereDoesntHave('alliance')
            ->pluck('alliance_id')
            ->unique()
            ->each(fn (int $allianceId) => AllianceInfoJob::dispatch($allianceId)->onQueue('high'));
    }

    private function handleFailedRequest(array $characterIds, EsiClient $esi): void
    {
        if (count($characterIds) === 1) {
            $this->handleSingleIdException($characterIds[0]);

            return;
        }
        $half = (int) ceil(count($characterIds) / 2);
        $this->updateOrCreateCharacterAffiliations(array_slice($characterIds, 0, $half), $esi);
        $this->updateOrCreateCharacterAffiliations(array_slice($characterIds, $half), $esi);
    }

    private function handleSingleIdException(int $characterId): void
    {
        CharacterInfoJob::dispatch($characterId)->onQueue('low');
        $invalidIds = Cache::get('invalid_character_ids', []);
        $invalidIds[] = $characterId;
        Cache::put('invalid_character_ids', $invalidIds, 60 * 24);
    }
}
