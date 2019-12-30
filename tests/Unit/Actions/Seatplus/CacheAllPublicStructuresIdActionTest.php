<?php


namespace Seatplus\Eveapi\Tests\Unit\Actions\Seatplus;


use Seatplus\Eveapi\Actions\Location\CacheAllPublicStrucutresIdAction;
use Seatplus\Eveapi\Models\Universe\Structure;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

class CacheAllPublicStructuresIdActionTest extends TestCase
{
    use MockRetrieveEsiDataAction;

    private $action;
    private $cache_string;

    public function setUp(): void
    {

        parent::setUp();

        $this->action = new CacheAllPublicStrucutresIdAction();
        $this->cache_string = 'new_public_structure_ids';
    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_caches_values_from_endpoint()
    {
        $mock_data = $this->buildMockEsiData();

        $this->action->execute();

        $this->assertTrue(cache()->has($this->cache_string));

    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_contains_cached_value()
    {
        $mock_data = $this->buildMockEsiData();

        $this->action->execute();

        $cached_ids = cache()->get($this->cache_string);

        $this->assertTrue(in_array(1031913422848, $cached_ids));

    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_does_contain_already_existing_strucutre_ids_older_then_a_week()
    {
        $mock_data = $this->buildMockEsiData();

        factory(Structure::class)->create([
            'structure_id' => 1030200598530,
            'updated_at' => carbon('now')->subWeek()->subDay()
        ]);

        $this->action->execute();

        $cached_ids = cache()->get($this->cache_string);

        $this->assertTrue(in_array(1030200598530, $cached_ids));

    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_does_not_contain_already_existing_strucutre_ids_younger_then_a_week()
    {
        $mock_data = $this->buildMockEsiData();

        factory(Structure::class)->create([
            'structure_id' => 1030200598530,
            'updated_at' => carbon('now')->subWeek()->addDay()
        ]);

        $this->action->execute();

        $cached_ids = cache()->get($this->cache_string);

        $this->assertFalse(in_array(1030200598530, $cached_ids));

    }

    private function buildMockEsiData()
    {

        $this->mockRetrieveEsiDataAction([
            1031913422848,
            1030200598530,
            1027528548355,
            1031556972547,
            1026858622981,
            1028156907526,
            1031424491525,
        ]);
    }


}
