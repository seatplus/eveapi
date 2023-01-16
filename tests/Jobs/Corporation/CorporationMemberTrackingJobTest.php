<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Esi\Jobs\Corporation\CorporationMemberTrackingAction;
use Seatplus\Eveapi\Jobs\Corporation\CorporationMemberTrackingJob;
use Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

uses(MockRetrieveEsiDataAction::class);

beforeEach(function () {
    Event::fakeFor(function () {
        updateRefreshTokenScopes($this->test_character->refresh_token, ['esi-corporations.track_members.v1'])->save();
        $this->test_character->roles()->update(['roles' => ['Director']]);
        updateCharacterRoles(['Director']);
    });
});

/**
 * @runTestsInSeparateProcesses
 */
test('if job is queued', function () {
    Queue::fake();

    // Assert that no jobs were pushed...
    Queue::assertNothingPushed();

    CorporationMemberTrackingJob::dispatch(testCharacter()->corporation->corporation_id)->onQueue('default');

    // Assert a job was pushed to a given queue...
    Queue::assertPushedOn('default', CorporationMemberTrackingJob::class);
});

/**
 * @runTestsInSeparateProcesses
 */
test('retrieve test', function () {
    buildCorporationMemberMockEsiData();

    // Stop Action dispatching a new job
    Bus::fake();

    $this->assertDatabaseMissing('corporation_member_trackings', [
        'corporation_id' => testCharacter()->corporation->corporation_id,
    ]);

    // expect testCharater to have director role and to have a refresh token with the correct scope
    expect(testCharacter()->roles->hasRole('roles', 'Director'))->toBeTrue()
        ->and(testCharacter()->refresh_token->hasScope('esi-corporations.track_members.v1'))->toBeTrue();

    // Run Action
    (new CorporationMemberTrackingJob(testCharacter()->corporation->corporation_id))->handle();

    //Assert that test character is now created
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

    mockRetrieveEsiDataAction([$mock_data->toArray()]);

    return $mock_data;
}
