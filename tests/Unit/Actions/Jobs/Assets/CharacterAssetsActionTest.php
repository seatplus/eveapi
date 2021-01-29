<?php


namespace Seatplus\Eveapi\Tests\Unit\Actions\Jobs\Assets;


use Seatplus\Eveapi\Actions\Jobs\Assets\CharacterAssetsAction;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

class CharacterAssetsActionTest extends TestCase
{

    use MockRetrieveEsiDataAction;


    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function retrieveTest()
    {

        $mock_data = $this->buildMockEsiData();

        // Run CharacterAssetsAction
        (new CharacterAssetsAction())->execute($this->test_character->refresh_token);


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
        (new CharacterAssetsAction())->execute($this->test_character->refresh_token);


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

            /*$this->assertDatabaseMissing('assets', [
                'assetable_id' => $this->test_character->character_id,
                'item_id' => $data->item_id
            ]);*/
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
