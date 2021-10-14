<?php


use Illuminate\Support\Facades\Bus;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Esi\Jobs\Corporation\CorporationInfoAction;
use Seatplus\Eveapi\Jobs\Corporation\CorporationInfoJob;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

uses(MockRetrieveEsiDataAction::class);

beforeEach(function () {
    $this->job_container = new JobContainer([
        'corporation_id' => $this->test_character->character_id,
    ]);
});

/**
 * @runTestsInSeparateProcesses
 */
test('retrieve test', function () {
    $mock_data = buildCorporationInfoMockEsiData();

    // Stop CharacterInfoAction dispatching a new job
    Bus::fake();

    // Run InfoAction
    //(new CorporationInfoAction)->execute($mock_data->corporation_id);
    (new CorporationInfoJob($this->job_container))->handle();

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
