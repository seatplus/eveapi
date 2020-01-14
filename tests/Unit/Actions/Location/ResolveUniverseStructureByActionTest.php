<?php


namespace Seatplus\Eveapi\Tests\Unit\Actions\Location;


use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Actions\Location\ResolveUniverseStructureByIdAction;
use Seatplus\Eveapi\Events\UniverseStructureCreated;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Structure;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

class ResolveUniverseStructureByActionTest extends TestCase
{
    use MockRetrieveEsiDataAction;

    private $refresh_token;

    public function setUp(): void
    {

        parent::setUp();
        $this->refresh_token = $this->test_character->refresh_token;

        Event::fake([
            UniverseStructureCreated::class
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

        (new ResolveUniverseStructureByIdAction($this->refresh_token))->execute($mock_data->structure_id);

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

        (new ResolveUniverseStructureByIdAction($this->refresh_token))->execute($mock_data->structure_id);

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

        (new ResolveUniverseStructureByIdAction($this->refresh_token))->execute($mock_data->structure_id);

        $location = Location::find($mock_data->structure_id);

        $this->assertInstanceOf(Structure::class,$location->locatable);
    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_does_not_create_structure_if_scope_is_missing()
    {
        $mock_data = $this->buildMockEsiData();

        $refresh_token = factory(RefreshToken::class)->make([
            'scopes' => []
        ]);

        //Assert that no structure is created
        $this->assertDatabaseMissing('universe_structures', [
            'structure_id' => $mock_data->structure_id
        ]);

        (new ResolveUniverseStructureByIdAction($refresh_token))->execute($mock_data->structure_id);

        //Assert that no structure is created
        $this->assertDatabaseMissing('universe_structures', [
            'structure_id' => $mock_data->structure_id
        ]);
    }

    private function buildMockEsiData()
    {

        $mock_data = factory(Structure::class)->make();

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        return $mock_data;
    }

}
