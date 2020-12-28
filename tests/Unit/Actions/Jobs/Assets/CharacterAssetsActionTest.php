<?php


namespace Seatplus\Eveapi\Tests\Unit\Actions\Jobs\Assets;


use Seatplus\Eveapi\Actions\Jobs\Assets\CharacterAssetsAction;
use Seatplus\Eveapi\Models\Assets\CharacterAsset;
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
            $this->assertDatabaseHas('character_assets', [
                'character_id' => $this->test_character->character_id,
                'item_id' => $data->item_id
            ]);
    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_cleans_up_assets()
    {

        $old_data = factory(CharacterAsset::class,5)->create([
            'character_id' => $this->test_character->character_id
        ]);

        // assert that old data is present before CharacterAssetsCleanUpAction
        foreach ($old_data as $data)
            //Assert that character asset created
            $this->assertDatabaseHas('character_assets', [
                'character_id' => $this->test_character->character_id,
                'item_id' => $data->item_id
            ]);


        $mock_data = $this->buildMockEsiData();

        // Run CharacterAssetsAction
        (new CharacterAssetsAction())->execute($this->test_character->refresh_token);


        foreach ($mock_data as $data)
            //Assert that character asset created
            $this->assertDatabaseHas('character_assets', [
                'character_id' => $this->test_character->character_id,
                'item_id' => $data->item_id
            ]);

        // assert if old data has been removed thanks to CharacterAssetsCleanupAction
        foreach ($old_data as $data)
            //Assert that character asset created
            $this->assertDatabaseMissing('character_assets', [
                'character_id' => $this->test_character->character_id,
                'item_id' => $data->item_id
            ]);
    }

    private function buildMockEsiData()
    {

        $mock_data = factory(CharacterAsset::class,5)->make([
            'character_id' => $this->test_character->character_id
        ]);

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        return $mock_data;
    }

}
