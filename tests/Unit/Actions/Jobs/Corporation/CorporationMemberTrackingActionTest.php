<?php

namespace Seatplus\Eveapi\Tests\Unit\Actions\Jobs\Corporation;

use Illuminate\Support\Facades\Bus;
use Seatplus\Eveapi\Actions\Jobs\Corporation\CorporationMemberTrackingAction;
use Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

class CorporationMemberTrackingActionTest extends TestCase
{

    use MockRetrieveEsiDataAction;

    protected function setUp(): void
    {

        parent::setUp();

        Bus::fake();
    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function retrieveTest()
    {

        $mock_data = $this->buildMockEsiData();

        // Stop Action dispatching a new job
        Bus::fake();

        $this->assertDatabaseMissing('corporation_member_trackings', [
            'corporation_id' => $this->test_character->corporation->corporation_id
        ]);

        // Run Action
        (new CorporationMemberTrackingAction)->execute($this->test_character->refresh_token);

        //Assert that test character is now created
        $this->assertDatabaseHas('corporation_member_trackings', [
            'corporation_id' => $this->test_character->corporation->corporation_id
        ]);
    }

    private function buildMockEsiData()
    {

        $mock_data = CorporationMemberTracking::factory()->make([
            'character_id' => $this->test_character->character_id,
            'corporation_id' => $this->test_character->corporation->corporation_id
        ]);

        $this->mockRetrieveEsiDataAction([$mock_data->toArray()]);

        return $mock_data;
    }


}
