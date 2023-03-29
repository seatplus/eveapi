<?php


use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Character\CharacterInfoJob as CharacterInfoJob;
use Seatplus\Eveapi\Jobs\Corporation\CorporationMemberTrackingJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Type;

beforeEach(function () {
    Queue::fake();
});

it('handles missing jobs after corporation member job', function (string $job_class, array $configuration, bool $should_be_queued = true) {

    // update refresh token to be valid
    updateRefreshTokenScopes(testCharacter()->refresh_token, ['esi-corporations.track_members.v1'])->save();

    expect(testCharacter()->refresh_token->scopes)->toContain('esi-corporations.track_members.v1');

    // add required roles to character
    updateCharacterRoles(['Director']);

    expect(testCharacter())->roles->roles->toContain('Director');

    $tracking = CorporationMemberTracking::factory()->make([
        'corporation_id' => testCharacter()->corporation->corporation_id,
        'character_id' => testCharacter()->character_id,
        ...$configuration
    ]);

    mockRetrieveEsiDataAction([$tracking->toArray()]);

    (new CorporationMemberTrackingJob($tracking->corporation_id))->handle();

    match ($should_be_queued) {
        true => Queue::assertPushedOn('high', $job_class),
        false => Queue::assertNotPushed($job_class),
    };

})->with([
    'resolves type if ship is unknown' => [ResolveUniverseTypeByIdJob::class, ['ship_type_id' => 1234]],
    'resolves location if location is unknown' => [ResolveLocationJob::class, ['location_id' => 1234]],
    'resolves character if character is unknown' => [CharacterInfoJob::class, ['character_id' => 1234]],
    'does not resolves type if ship is known' => fn() => [ResolveUniverseTypeByIdJob::class, ['ship_type_id' => Type::factory()->create()->type_id], false],
    'does not resolves location if location is known' => fn() => [ResolveLocationJob::class, ['location_id' => Location::factory()->create()->location_id], false],
    'does not resolves character if character is known' => fn() => [CharacterInfoJob::class, ['location_id' => CharacterInfo::factory()->create()->character_id], false],
]);




