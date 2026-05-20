<?php

namespace Seatplus\Eveapi\Jobs\Corporation;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\Resources\Corporation\GetCorporationsCorporationIdDivisions;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Models\Corporation\CorporationDivision;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Services\FindCorporationRefreshToken;

class CorporationDivisionsJob extends EsiJob
{
    protected const string OPERATION_CLASS = GetCorporationsCorporationIdDivisions::class;

    public function __construct(public int $corporation_id) {}

    #[\Override]
    public function getRefreshToken(): ?RefreshToken
    {
        $token = (new FindCorporationRefreshToken)($this->corporation_id, 'esi-corporations.read_divisions.v1', 'Director');
        throw_unless($token, new \Exception("No eligible refresh token found for corporation {$this->corporation_id}"));

        return $token;
    }

    #[\Override]
    public function tags(): array
    {
        return ['corporation', "corporation_id:{$this->corporation_id}", 'divisions'];
    }

    #[\Override]
    protected function executeJob(EsiClient $esi): void
    {
        $response = GetCorporationsCorporationIdDivisions::execute($esi, $this->corporation_id);
        if ($response->isCachedLoad) {
            return;
        }

        $divisions = collect();

        foreach (['hangar' => $response->hangar ?? [], 'wallet' => $response->wallet ?? []] as $divisionType => $entries) {
            collect($entries)->each(fn (mixed $entry) => $divisions->push([
                'corporation_id' => $this->corporation_id,
                'division_type' => $divisionType,
                'division_id' => ((object) $entry)->division,
                'name' => ((object) $entry)->name ?? '',
            ]));
        }

        CorporationDivision::upsert(
            $divisions->toArray(),
            ['corporation_id', 'division_type', 'division_id'],
            ['name']
        );
    }
}
