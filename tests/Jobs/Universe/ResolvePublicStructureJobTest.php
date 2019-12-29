<?php


namespace Seatplus\Eveapi\Tests\Jobs\Universe;


use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Jobs\Universe\ResolvePublicStructureJob;
use Seatplus\Eveapi\Models\Assets\CharacterAsset;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\Structure;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

class ResolvePublicStructureJobTest extends TestCase
{
    use MockRetrieveEsiDataAction;

    protected $location_id;

    /**
     * @var string
     */
    private $cache_string;

    public function setUp(): void
    {

        parent::setUp();

        $this->location_id = 1031913422848;
        $this->cache_string = 'new_public_structure_ids';
    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_creates_structure_from_cache()
    {

        cache()->put($this->cache_string, $this->location_id);

        $mock_data = $this->buildMockEsiData();

        $this->buildJob()->handle();

        $this->assertNotNull(Location::find($this->location_id)->locatable);
    }

    private function buildMockEsiData()
    {
        $mock_data = factory(Structure::class)->make([
            'structure_id' => $this->location_id
        ]);

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        return $mock_data;
    }

    private function buildJob() : ResolvePublicStructureJob
    {
        $job_container = new JobContainer([
            'refresh_token' => $this->test_character->refresh_token,
        ]);

        return new ResolvePublicStructureJob($job_container);
    }

}
