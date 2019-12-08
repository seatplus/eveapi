<?php


namespace Seatplus\Eveapi\Tests\Unit\Actions\Jobs\Universe;


use Faker\Guesser\Name;
use Illuminate\Support\Facades\Cache;
use Seatplus\Eveapi\Actions\Jobs\Universe\NamesAction;
use Seatplus\Eveapi\Models\Assets\CharacterAsset;
use Seatplus\Eveapi\Models\Universe\Names;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

class NamesTest extends TestCase
{

    use MockRetrieveEsiDataAction;

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_returns_name_for_id()
    {
        $mock_data = $this->buildMockEsiData();

        $first_name = $mock_data->first();

        $result = (new NamesAction())->execute($first_name->id);

        $this->assertDatabaseHas('universe_names', [
            'name' => $first_name->name
        ]);

        $this->assertEquals($first_name->name, $result->name);

    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_runs_with_cached_ids()
    {
        $mock_data = $this->buildMockEsiData(5);

        Cache::put('type_ids_to_resolve', collect($mock_data)->pluck('id'));

        $name_action = new NamesAction;
        $result = $name_action->execute();

        foreach ($mock_data as $type) {
            $this->assertDatabaseHas('universe_names', [
                'name' => $type->name
            ]);
        }

        $this->assertNull($result);

        $this->assertTrue(is_array($name_action->getRequestBody()));

        $this->assertEquals(collect($mock_data)->pluck('id')->all(), $name_action->getRequestBody());

    }

    private function buildMockEsiData(int $times = 1)
    {
        $mock_data = factory(Names::class, $times)->make();

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        return $mock_data;
    }

}
