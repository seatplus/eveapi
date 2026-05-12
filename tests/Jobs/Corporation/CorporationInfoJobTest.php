<?php

use Illuminate\Support\Facades\Bus;
use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\Eveapi\Jobs\Corporation\CorporationInfoJob;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;

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

    // Assert that test character is now created
    $this->assertDatabaseHas('corporation_infos', [
        'name' => $mock_data->name,
    ]);
});

test('returns early if cached', function () {

    $response = mock(EsiResponse::class);
    $response->shouldReceive('isCachedLoad')->once()->andReturn(true);

    $job = mock(CorporationInfoJob::class)->makePartial();
    $job->shouldReceive('retrieve')->once()->andReturn($response);

    $job->executeJob();

    expect(true)->toBeTrue();
});

// Helpers
function buildCorporationInfoMockEsiData()
{
    $mock_data = CorporationInfo::factory()->make([
        'date_founded' => now()->toDateString(),
    ]);

    mockRetrieveEsiDataAction($mock_data->toArray());

    return $mock_data;
}
