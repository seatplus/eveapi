<?php

namespace Seatplus\Eveapi\Jobs\Universe;

use Illuminate\Queue\Attributes\MaxExceptions;
use Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis;
use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\Resources\Universe\GetUniverseStructuresStructureId;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Jobs\Middleware\EsiProactiveRateLimitMiddleware;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Structure;

#[MaxExceptions(1)]
final class ResolveUniverseStructureByIdJob extends EsiJob
{
    protected const string OPERATION_CLASS = GetUniverseStructuresStructureId::class;

    public function __construct(
        public int $character_id,
        public int $location_id
    ) {}

    #[\Override]
    public function tags(): array
    {
        return ['resolve', 'universe', 'structure', "location_id:{$this->location_id}"];
    }

    #[\Override]
    public function middleware(): array
    {
        return [
            new EsiProactiveRateLimitMiddleware,
            (new ThrottlesExceptionsWithRedis(40 / 2, 5 * 60))
                ->by('esiratelimit')
                ->backoff(5),
        ];
    }

    #[\Override]
    public function getRefreshToken(): RefreshToken
    {
        return RefreshToken::findOrFail($this->character_id);
    }

    #[\Override]
    protected function executeJob(EsiClient $esi): void
    {
        $response = self::OPERATION_CLASS::execute($esi, $this->location_id);
        if ($response->isCachedLoad) {
            return;
        }

        Structure::updateOrCreate(['structure_id' => $this->location_id], [
            'name' => $response->name,
            'owner_id' => $response->owner_id,
            'solar_system_id' => $response->solar_system_id,
            'type_id' => $response->type_id ?? null,
        ])->touch();

        Location::updateOrCreate(['location_id' => $this->location_id], [
            'locatable_id' => $this->location_id,
            'locatable_type' => Structure::class,
        ]);
    }
}
