<?php


namespace Seatplus\Eveapi\Tests\Unit\Actions\Jobs\Universe;



use Illuminate\Support\Facades\Cache;
use Seatplus\Eveapi\Actions\Jobs\Universe\ResolveUniverseCategoriesByCategoryIdAction;
use Seatplus\Eveapi\Models\Universe\Category;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

class CategoriesTest extends TestCase
{

    use MockRetrieveEsiDataAction;

    private $action;
    private $cache_string;

    public function setUp(): void
    {

        parent::setUp();

        $this->action = new ResolveUniverseCategoriesByCategoryIdAction;
        $this->cache_string = 'category_ids_to_resolve';

    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_returns_name_for_id()
    {
        $mock_data = $this->buildMockEsiData();

        $result = $this->action->execute($mock_data->category_id);

        $this->assertDatabaseHas('universe_categories', [
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

        Cache::put($this->cache_string, collect($mock_data)->pluck('category_id')->all());

        $result = $this->action->execute();

        $this->assertDatabaseHas('universe_categories', [
            'name' => $mock_data->name,
            'category_id' => $mock_data->category_id
        ]);


        $this->assertNull($result);

    }

    private function buildMockEsiData()
    {
        $mock_data = Category::factory()->make();

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        return $mock_data;
    }


}
