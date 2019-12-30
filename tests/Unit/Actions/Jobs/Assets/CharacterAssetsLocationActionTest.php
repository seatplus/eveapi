<?php


namespace Seatplus\Eveapi\Tests\Unit\Actions\Jobs\Assets;


use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Actions\Jobs\Assets\CharacterAssetsLocationAction;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Models\Assets\CharacterAsset;
use Seatplus\Eveapi\Tests\TestCase;

class CharacterAssetsLocationActionTest extends TestCase
{

    /**
     * @var \Seatplus\Eveapi\Actions\Jobs\Assets\CharacterAssetsLocationAction
     */
    private $action;

    public function setUp(): void
    {

        parent::setUp();

        $this->action = new CharacterAssetsLocationAction($this->test_character->refresh_token);
    }

    /** @test */
    public function it_builds_location_id()
    {
        $test_assets = factory(CharacterAsset::class,5)->create([
            'character_id' => $this->test_character->character_id,
            'location_flag' => 'Hangar',
            'location_type' => 'other'
        ]);

        $this->action->buildLocationIds();

        foreach ($test_assets as $test_asset)
            $this->assertTrue(in_array($test_asset->location_id, $this->action->getLocationIds()->toArray()));
    }

    /** @test */
    public function it_dispatches_resolve_location_job()
    {
        Queue::fake();

        // Assert that no jobs were pushed...
        Queue::assertNothingPushed();

        $test_assets = factory(CharacterAsset::class,5)->create([
            'character_id' => $this->test_character->character_id,
            'location_flag' => 'Hangar',
            'location_type' => 'other'
        ]);

        $this->action->buildLocationIds()->execute();

         $test_assets->pluck('location_id')->unique()->each(function ($location_id) {
             Queue::assertPushed(ResolveLocationJob::class, function ($job) use ($location_id) {
                 return $job->location_id === $location_id;
             });
         });

        // Assert a job was pushed once per test asset...
        Queue::assertPushed(ResolveLocationJob::class, $test_assets->count());


    }

}
