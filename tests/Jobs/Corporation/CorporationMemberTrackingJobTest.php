<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Seatplus\EsiClient\EsiClient;
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
    Queue::fake();

    $mock_data = CorporationMemberTracking::factory()->make([
        'character_id' => testCharacter()->character_id,
        'corporation_id' => testCharacter()->corporation->corporation_id,
    ]);

    Bus::fake();

    expect(testCharacter()->roles->hasRole('roles', 'Director'))->toBeTrue()
        ->and(testCharacter()->refresh_token->hasScope('esi-corporations.track_members.v1'))->toBeTrue();

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([(object) $mock_data->toArray()]));

    $job = new CorporationMemberTrackingJob(testCharacter()->corporation->corporation_id);
    $job->executeJob($esi);

    expect(CorporationMemberTracking::where('corporation_id', $this->test_character->corporation->corporation_id)->exists())->toBeTrue();
});
