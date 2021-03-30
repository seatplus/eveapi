<?php

namespace Seatplus\Eveapi\Tests\Jobs\Assets;

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetJob;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

class CharacterAssetTest extends TestCase
{

    use MockRetrieveEsiDataAction;

    protected JobContainer $job_container;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->job_container = new JobContainer([
            'refresh_token' => $this->test_character->refresh_token
        ]);
    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function testIfJobIsQueued()
    {
        Queue::fake();

        // Assert that no jobs were pushed...
        Queue::assertNothingPushed();

        CharacterAssetJob::dispatch($this->job_container)->onQueue('default');

        // Assert a job was pushed to a given queue...
        Queue::assertPushedOn('default', CharacterAssetJob::class);
    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function retrieveTest()
    {

        $mock_data = $this->buildMockEsiData();


        // Run job
        (new CharacterAssetJob($this->job_container))->handle();


        foreach ($mock_data as $data)
            //Assert that character asset created
            $this->assertDatabaseHas('assets', [
                'assetable_id' => $this->test_character->character_id,
                'item_id' => $data->item_id
            ]);
    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_cleans_up_assets()
    {

        $old_data = Asset::factory()->count(5)->create([
            'assetable_id' => $this->test_character->character_id
        ]);

        // assert that old data is present before CharacterAssetsCleanUpAction
        foreach ($old_data as $data)
            //Assert that character asset created
            $this->assertDatabaseHas('assets', [
                'assetable_id' => $this->test_character->character_id,
                'item_id' => $data->item_id
            ]);


        $mock_data = $this->buildMockEsiData();

        // Run CharacterAssetsAction
        (new CharacterAssetJob($this->job_container))->handle();


        foreach ($mock_data as $data)
            //Assert that character asset created
            $this->assertDatabaseHas('assets', [
                'assetable_id' => $this->test_character->character_id,
                'item_id' => $data->item_id
            ]);

        // assert if old data has been removed thanks to CharacterAssetsCleanupAction
        foreach ($old_data as $data)
            $this->assertCount(0, Asset::where('assetable_id', $this->test_character->character_id)
                ->where('item_id', $data->item_id)
                ->get()
            );

    }

    private function buildMockEsiData()
    {

        $mock_data = Asset::factory()->count(5)->make([
            'assetable_id' => $this->test_character->character_id
        ]);

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        return $mock_data;
    }

}
