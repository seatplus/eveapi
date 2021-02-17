<?php


namespace Seatplus\Eveapi\Tests\Unit\Actions\Location;


use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Actions\Location\ResolveUniverseStationByIdAction;
use Seatplus\Eveapi\Events\UniverseStationCreated;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

class ResolveUniverseStationByIdActionTest extends TestCase
{
    use MockRetrieveEsiDataAction;

    private $refresh_token;

    public function setUp(): void
    {

        parent::setUp();

        $this->refresh_token = $this->test_character->refresh_token;

        Event::fake([
            UniverseStationCreated::class
        ]);
    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_creates_Station()
    {
        $mock_data = $this->buildMockEsiData();

        $this->assertNull(Station::find($mock_data->station_id));

        (new ResolveUniverseStationByIdAction)->execute($mock_data->station_id);

        //Assert that structure is created
        $this->assertDatabaseHas('universe_stations', [
            'station_id' => $mock_data->station_id
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
            'location_id' => $mock_data->station_id
        ]);

        (new ResolveUniverseStationByIdAction)->execute($mock_data->station_id);

        //Assert that structure is created
        $this->assertDatabaseHas('universe_locations', [
            'location_id' => $mock_data->station_id
        ]);
    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_creates_polymorphic_relationship()
    {
        $mock_data = $this->buildMockEsiData();

        (new ResolveUniverseStationByIdAction)->execute($mock_data->station_id);

        $location = Location::find($mock_data->station_id);

        $this->assertInstanceOf(Station::class, $location->locatable);
    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_does_not_create_structure_if_location_id_is_not_in_range()
    {
        $mock_data = Station::factory()->make([
            'station_id' => 1234,
        ]);

        (new ResolveUniverseStationByIdAction)->execute($mock_data->station_id);

        //Assert that no structure is created
        $this->assertDatabaseMissing('universe_stations', [
            'station_id' => $mock_data->station_id
        ]);
    }


    private function buildMockEsiData()
    {

        $mock_data = Station::factory()->make();

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        return $mock_data;
    }

}
