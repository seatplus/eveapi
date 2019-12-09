<?php


namespace Seatplus\Eveapi\Tests\Unit\Actions\Jobs\Universe;



use Illuminate\Support\Facades\Cache;
use Seatplus\Eveapi\Actions\Jobs\Universe\ResolveUniverseTypesByTypeIdAction;
use Seatplus\Eveapi\Models\Universe\Types;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

class TypesTest extends TestCase
{

    use MockRetrieveEsiDataAction;

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_returns_name_for_id()
    {
        $mock_data = $this->buildMockEsiData();

        $result = (new ResolveUniverseTypesByTypeIdAction())->execute($mock_data->type_id);

        $this->assertDatabaseHas('universe_types', [
            'name' => $mock_data->name
        ]);

        $this->assertEquals($mock_data->name, $result->name);

    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_runs_with_cached_ids()
    {
        $mock_data = $this->buildMockEsiData();

        Cache::put('type_ids_to_resolve', collect($mock_data)->pluck('type_id')->all());

        $action = new ResolveUniverseTypesByTypeIdAction;
        $result = $action->execute();

        $this->assertDatabaseHas('universe_types', [
            'name' => $mock_data->name,
            'type_id' => $mock_data->type_id
        ]);


        $this->assertNull($result);

    }

    private function buildMockEsiData()
    {
        $mock_data = factory(Types::class)->make();

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        return $mock_data;
    }


}
