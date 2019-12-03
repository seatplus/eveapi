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

        // Run InfoAction
        (new CharacterAssetsAction())->execute($this->test_character->refresh_token);


        //Assert that alliance_info is created
        $this->assertDatabaseHas('character_assets', [
            'character_id' => $this->test_character->character_id
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
