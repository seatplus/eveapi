<?php


namespace Seatplus\Eveapi\Tests\Unit\Actions\Jobs\Universe;



use Illuminate\Support\Facades\Cache;
use Seatplus\Eveapi\Actions\Jobs\Universe\ResolveUniverseGroupsByGroupIdAction;
use Seatplus\Eveapi\Actions\Jobs\Universe\ResolveUniverseTypesByTypeIdAction;
use Seatplus\Eveapi\Models\Universe\Group;
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

class GroupsTest extends TestCase
{

    use MockRetrieveEsiDataAction;

    private $action;

    public function setUp(): void
    {

        parent::setUp();

        $this->action = new ResolveUniverseGroupsByGroupIdAction;

    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_returns_name_for_id()
    {
        $mock_data = $this->buildMockEsiData();

        $result = $this->action->execute($mock_data->group_id);

        $this->assertDatabaseHas('universe_groups', [
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

        Cache::put('group_ids_to_resolve', collect($mock_data)->pluck('group_id')->all());

        $result = $this->action->execute();

        $this->assertDatabaseHas('universe_groups', [
            'name' => $mock_data->name,
            'group_id' => $mock_data->group_id
        ]);


        $this->assertNull($result);

    }

    private function buildMockEsiData()
    {
        $mock_data = factory(Group::class)->make();

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        return $mock_data;
    }


}
