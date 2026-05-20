<?php

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

class CharacterAffiliationJob extends EsiJob
{
    protected const string OPERATION_CLASS = PostCharactersAffiliation::class;

    private array $manual_ids = [];

    private Collection $character_affiliations;

    public function __construct(int|array $character_ids)
    {
        $this->setManualIds($character_ids);
        throw_unless(count($this->manual_ids) <= 1000, new \Exception('Character ids must not exceed 1000'));
        $this->character_affiliations = collect();
    }

    #[\Override]
    public function tags(): array
    {
        return ['character', 'affiliation'];
    }

    #[\Override]
    protected function executeJob(EsiClient $esi): void
    {
        $this->updateOrCreateCharacterAffiliations($this->getManualIds(), $esi);

        CharacterAffiliation::query()->upsert(
            $this->character_affiliations->toArray(),
            ['character_id'],
            ['corporation_id', 'alliance_id', 'faction_id', 'last_pulled']
        );

        $this->followUp();
    }

    public function getManualIds(): array
    {
        return $this->manual_ids;
    }

    public function setManualIds(int|array $manual_ids): void
    {
        $this->manual_ids = is_array($manual_ids) ? $manual_ids : [$manual_ids];
    }

    private function updateOrCreateCharacterAffiliations(array $characterIds, EsiClient $esi): void
    {
        $timestamp = now();
        try {
            $response = static::OPERATION_CLASS::execute($esi, $characterIds);
            foreach ($response->data as $result) {
                $this->character_affiliations->push([
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
