<?php


namespace Seatplus\Eveapi\Tests\Unit\Services\Location;


use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseStructureByIdJob;
use Seatplus\Eveapi\Events\RefreshTokenCreated;
use Seatplus\Eveapi\Events\UniverseStructureCreated;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Structure;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

class ResolveUniverseStructureByIdJobTest extends TestCase
{
    use MockRetrieveEsiDataAction;

    private $refresh_token;

    public function setUp(): void
    {

        parent::setUp();

        Event::fake([
            UniverseStructureCreated::class,
            RefreshTokenCreated::class
        ]);

        $this->refresh_token = RefreshToken::factory()->create([
            'scopes' => ['esi-universe.read_structures.v1']
        ]);
    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_creates_structure()
    {
        $mock_data = $this->buildMockEsiData();

        //Assert that no structure is created
        $this->assertDatabaseMissing('universe_structures', [
            'structure_id' => $mock_data->structure_id
        ]);

        (new ResolveUniverseStructureByIdJob($this->refresh_token, $mock_data->structure_id))->handle();

        //Assert that structure is created
        $this->assertDatabaseHas('universe_structures', [
            'structure_id' => $mock_data->structure_id
        ]);
    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_creates_location()
    {
        $mock_data = $this->buildMockEsiData();

        //Assert that no structure is created
        $this->assertDatabaseMissing('universe_locations', [
            'location_id' => $mock_data->structure_id
        ]);

        (new ResolveUniverseStructureByIdJob($this->refresh_token, $mock_data->structure_id))->handle();

        //Assert that structure is created
        $this->assertDatabaseHas('universe_locations', [
            'location_id' => $mock_data->structure_id
        ]);
    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_creates_polymorphic_relationship()
    {
        $mock_data = $this->buildMockEsiData();

        (new ResolveUniverseStructureByIdJob($this->refresh_token, $mock_data->structure_id))->handle();

        $location = Location::find($mock_data->structure_id);

        $this->assertInstanceOf(Structure::class,$location->locatable);
    }

    private function buildMockEsiData()
    {

        $mock_data = Structure::factory()->make();

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        return $mock_data;
    }

}
