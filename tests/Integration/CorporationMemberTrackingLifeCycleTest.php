<?php

use Illuminate\Support\Facades\Queue;
use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Character\CharacterInfoJob;
use Seatplus\Eveapi\Jobs\Corporation\CorporationMemberTrackingJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Type;

beforeEach(function () {
    Queue::fake();
});

it('handles missing jobs after corporation member job', function (string $jobClass, array $configuration, bool $shouldBeQueued = true) {
    updateRefreshTokenScopes(testCharacter()->refreshToken, ['esi-corporations.track_members.v1'])->save();

    expect(testCharacter()->refreshToken->scopes)->toContain('esi-corporations.track_members.v1');

    updateCharacterRoles(['Director']);

    expect(testCharacter())->roles->roles->toContain('Director');

    $tracking = CorporationMemberTracking::factory()->make([
        'corporation_id' => testCharacter()->corporation->corporation_id,
        'character_id' => testCharacter()->character_id,
        ...$configuration,
    ]);

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([(object) $tracking->toArray()]));

    $job = new CorporationMemberTrackingJob($tracking->corporation_id);
    $job->executeJob($esi);

    match ($shouldBeQueued) {
        true => Queue::assertPushedOn('high', $jobClass),
        false => Queue::assertNotPushed($jobClass),
    };
})->with([
    'resolves type if ship is unknown' => [ResolveUniverseTypeByIdJob::class, ['ship_type_id' => 1234]],
    'resolves location if location is unknown' => [ResolveLocationJob::class, ['location_id' => 1234]],
    'resolves character if character is unknown' => [CharacterInfoJob::class, ['character_id' => 1234]],
    'does not resolves type if ship is known' => fn () => [ResolveUniverseTypeByIdJob::class, ['ship_type_id' => Type::factory()->create()->type_id], false],
    'does not resolves location if location is known' => fn () => [ResolveLocationJob::class, ['location_id' => Location::factory()->create()->location_id], false],
    'does not resolves character if character is known' => fn () => [CharacterInfoJob::class, ['location_id' => CharacterInfo::factory()->create()->character_id], false],
]);
