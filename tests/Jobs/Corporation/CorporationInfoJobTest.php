<?php

use Illuminate\Support\Facades\Bus;
use Seatplus\Eveapi\Jobs\Corporation\CorporationInfoJob;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

uses(MockRetrieveEsiDataAction::class);

beforeEach(function () {
    $this->corporation_id = testCharacter()->corporation->corporation_id;
});

/**
 * @runTestsInSeparateProcesses
 */
test('retrieve test', function () {
    $mock_data = buildCorporationInfoMockEsiData();

    // Stop CharacterInfoAction dispatching a new job
    Bus::fake();

    // Run InfoAction
    (new CorporationInfoJob($this->corporation_id))->handle();

    //Assert that test character is now created
    $this->assertDatabaseHas('corporation_infos', [
        'name' => $mock_data->name,
    ]);
});

// Helpers
function buildCorporationInfoMockEsiData()
{
    $mock_data = CorporationInfo::factory()->make();

    mockRetrieveEsiDataAction($mock_data->toArray());

    return $mock_data;
}
