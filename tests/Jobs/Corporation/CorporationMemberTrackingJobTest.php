<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Corporation\CorporationMemberTrackingJob;
use Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking;

beforeEach(function () {
    Event::fakeFor(function () {
        updateRefreshTokenScopes($this->test_character->refresh_token, ['esi-corporations.track_members.v1'])->save();
        $this->test_character->roles()->update(['roles' => ['Director']]);
        updateCharacterRoles(['Director']);
    });
});

test('if job is queued', function () {
    Queue::fake();

    Queue::assertNothingPushed();

    CorporationMemberTrackingJob::dispatch(testCharacter()->corporation->corporation_id)->onQueue('default');

    Queue::assertPushedOn('default', CorporationMemberTrackingJob::class);
});

test('retrieve test', function () {
    $mock_data = buildCorporationMemberMockEsiData();

    Bus::fake();

    $this->assertDatabaseMissing('corporation_member_trackings', [
        'corporation_id' => testCharacter()->corporation->corporation_id,
    ]);

    expect(testCharacter()->roles->hasRole('roles', 'Director'))->toBeTrue()
        ->and(testCharacter()->refresh_token->hasScope('esi-corporations.track_members.v1'))->toBeTrue();

    runJob(new CorporationMemberTrackingJob(testCharacter()->corporation->corporation_id));

    $this->assertDatabaseHas('corporation_member_trackings', [
        'corporation_id' => $this->test_character->corporation->corporation_id,
    ]);
});

// Helpers
function buildCorporationMemberMockEsiData()
{
    $mock_data = CorporationMemberTracking::factory()->make([
        'character_id' => testCharacter()->character_id,
        'corporation_id' => testCharacter()->corporation->corporation_id,
    ]);

    mockEsiClient(
        'corporation->getCorporationsCorporationIdMembertracking',
        makeEsiResult([(object) $mock_data->toArray()])
    );

    return $mock_data;
}
