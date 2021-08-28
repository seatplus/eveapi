<?php


namespace Seatplus\Eveapi\Tests\Jobs\Corporation;

use Illuminate\Support\Facades\Bus;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Esi\Jobs\Corporation\CorporationInfoAction;
use Seatplus\Eveapi\Jobs\Corporation\CorporationInfoJob;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

class CorporationInfoJobTest extends TestCase
{
    use MockRetrieveEsiDataAction;

    protected JobContainer $job_container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->job_container = new JobContainer([
            'corporation_id' => $this->test_character->character_id,
        ]);
    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function retrieveTest()
    {
        $mock_data = $this->buildMockEsiData();

        // Stop CharacterInfoAction dispatching a new job
        Bus::fake();

        // Run InfoAction
        //(new CorporationInfoAction)->execute($mock_data->corporation_id);
        (new CorporationInfoJob($this->job_container))->handle();

        //Assert that test character is now created
        $this->assertDatabaseHas('corporation_infos', [
            'name' => $mock_data->name,
        ]);
    }

    private function buildMockEsiData()
    {
        $mock_data = CorporationInfo::factory()->make();

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        return $mock_data;
    }
}
