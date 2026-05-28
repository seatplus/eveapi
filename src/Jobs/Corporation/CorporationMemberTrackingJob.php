<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Jobs\Corporation;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\Resources\Corporation\GetCorporationsCorporationIdMembertracking;
use Seatplus\Eveapi\Jobs\Character\CharacterInfoJob;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Services\FindCorporationRefreshToken;

final class CorporationMemberTrackingJob extends EsiJob
{
    protected const string OPERATION_CLASS = GetCorporationsCorporationIdMembertracking::class;

    public function __construct(public int $corporation_id) {}

    #[\Override]
    public function getRefreshToken(): RefreshToken
    {
        $token = (new FindCorporationRefreshToken)($this->corporation_id, 'esi-corporations.track_members.v1', 'Director');
        throw_unless($token, new \Exception("No eligible refresh token found for corporation {$this->corporation_id}"));

        return $token;
    }

    #[\Override]
    public function tags(): array
    {
        return ['corporation', "corporation_id:{$this->corporation_id}", 'member', 'tracking'];
    }

    #[\Override]
    public function executeJob(EsiClient $esi): void
    {
        $response = self::OPERATION_CLASS::execute($esi, $this->corporation_id);
        if ($response->isCachedLoad) {
            return;
        }

        $members = collect($response->data)->map(fn (object $member) => [
            'corporation_id' => $this->corporation_id,
            'character_id' => $member->character_id,
            'start_date' => isset($member->start_date) ? carbon($member->start_date) : null,
            'base_id' => $member->base_id ?? null,
            'logon_date' => isset($member->logon_date) ? carbon($member->logon_date) : null,
            'logoff_date' => isset($member->logoff_date) ? carbon($member->logoff_date) : null,
            'location_id' => $member->location_id ?? null,
            'ship_type_id' => $member->ship_type_id ?? null,
        ]);

        CorporationMemberTracking::upsert($members->toArray(), ['corporation_id', 'character_id']);

        CorporationMemberTracking::where('corporation_id', $this->corporation_id)
            ->whereNotIn('character_id', $members->pluck('character_id')->all())
            ->get()
            ->each(fn (CorporationMemberTracking $ex_member) => $ex_member->delete());

        $this->getMemberCharacterInfo();
        $this->getLocations();
        $this->getShipTypes();
    }

    private function getLocations(): void
    {
        $refreshToken = $this->getRefreshToken();
        CorporationMemberTracking::query()
            ->where('corporation_id', $this->corporation_id)
            ->doesntHave('location')
            ->pluck('location_id')
            ->unique()
            ->each(fn (int $locationId) => ResolveLocationJob::dispatch($locationId, $refreshToken)->onQueue('high'));
    }

    private function getMemberCharacterInfo(): void
    {
        CorporationMemberTracking::query()
            ->where('corporation_id', $this->corporation_id)
            ->doesntHave('character')
            ->pluck('character_id')
            ->unique()
            ->each(fn (int $characterId) => CharacterInfoJob::dispatch($characterId)->onQueue('high'));
    }

    private function getShipTypes(): void
    {
        CorporationMemberTracking::query()
            ->where('corporation_id', $this->corporation_id)
            ->doesntHave('ship')
            ->pluck('ship_type_id')
            ->unique()
            ->each(fn (int $shipTypeId) => ResolveUniverseTypeByIdJob::dispatch($shipTypeId)->onQueue('high'));
    }
}
