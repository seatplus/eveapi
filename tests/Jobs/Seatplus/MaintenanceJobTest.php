<?php


namespace Seatplus\Eveapi\Tests\Jobs\Seatplus;


use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetsNameJob;
use Seatplus\Eveapi\Jobs\Character\CharacterInfoJob;
use Seatplus\Eveapi\Jobs\Seatplus\MaintenanceJob;
use Seatplus\Eveapi\Jobs\Seatplus\ResolveUniverseCategoriesByCategoryIdJob;
use Seatplus\Eveapi\Jobs\Seatplus\ResolveUniverseGroupsByGroupIdJob;
use Seatplus\Eveapi\Jobs\Seatplus\ResolveUniverseTypesByTypeIdJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking;
use Seatplus\Eveapi\Models\Universe\Group;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Models\Wallet\WalletTransaction;
use Seatplus\Eveapi\Tests\TestCase;

class MaintenanceJobTest extends TestCase
{
    /**
     * @var \Seatplus\Eveapi\Jobs\Seatplus\MaintenanceJob
     */
    private MaintenanceJob $job;

    public function setUp(): void
    {

        parent::setUp();

        $this->job = new MaintenanceJob;

    }

    /** @test */
    public function it_fetches_missing_types_from_assets()
    {
        $asset = Event::fakeFor(fn() => Asset::factory()->create());

        $this->job->handle();

        Queue::assertPushedOn('high', ResolveUniverseTypesByTypeIdJob::class);

        $this->assertTrue(in_array($asset->type_id,cache('type_ids_to_resolve')));
    }

    /** @test */
    public function it_fetches_missing_types_from_locations()
    {

        $station = Event::fakeFor(fn() => factory(Station::class)->create());
        $location = Event::fakeFor(fn() => factory(Location::class)->create([
            'location_id' => $station->station_id,
            'locatable_id' => $station->station_id,
            'locatable_type' => Station::class,
        ]));

        $this->job->handle();

        Queue::assertPushedOn('high', ResolveUniverseTypesByTypeIdJob::class);

        $this->assertTrue(in_array($station->type_id, cache('type_ids_to_resolve')));
    }

    /** @test */
    public function it_caches_missing_groups_from_type()
    {
        $type = Event::fakeFor(fn() => Type::factory()->create());

        $this->job->handle();

        Queue::assertPushedOn('high', ResolveUniverseGroupsByGroupIdJob::class);

        $this->assertTrue(in_array($type->group_id, cache('group_ids_to_resolve')));
    }

    /** @test */
    public function it_caches_missing_categories_from_group()
    {
        $group = Event::fakeFor(fn() => factory(Group::class)->create());

        $this->job->handle();

        Queue::assertPushedOn('high', ResolveUniverseCategoriesByCategoryIdJob::class);

        $this->assertTrue(in_array($group->category_id, cache('category_ids_to_resolve')));
    }

    /** @test */
    public function it_dispatch_resolve_location_jog_for_missing_assets_location()
    {
        $asset = Event::fakeFor(fn() => Asset::factory()->create([
            'assetable_id' => $this->test_character->character_id,
            'location_flag' => 'Hangar'
        ]));

        $this->job->handle();

        Queue::assertPushedOn('high', ResolveLocationJob::class);
    }

    /** @test */
    public function it_dispatch_resolve_missing_assets_name_jog()
    {
        $asset = Event::fakeFor(fn() => Asset::factory()->create([
            'assetable_id' => $this->test_character->character_id,
            'location_flag' => 'Hangar'
        ]));

        $type = Event::fakeFor(fn() => Type::factory()->create([
            'type_id' => $asset->type_id,
            'group_id' => factory(Group::class)->create(['category_id' => 2])
        ]));

        $this->job->handle();

        Queue::assertPushedOn('high', CharacterAssetsNameJob::class);
    }

    /** @test */
    public function it_dispatch_resolve_location_jog_for_missing_corporation_member_tracking_location()
    {
        Event::fakeFor(fn() => factory(CorporationMemberTracking::class)->create([
            'character_id' => $this->test_character->character_id,
        ]));

        $this->job->handle();

        Queue::assertPushedOn('high', ResolveLocationJob::class);
    }

    /** @test */
    public function it_fetches_missing_types_from_corporation_member_tracking()
    {
        $corporation_member_tracking = Event::fakeFor(fn() => factory(CorporationMemberTracking::class)->create());

        $this->job->handle();

        Queue::assertPushedOn('high', ResolveUniverseTypesByTypeIdJob::class);

        $this->assertTrue(in_array($corporation_member_tracking->ship_type_id, cache('type_ids_to_resolve')));
    }

    /** @test */
    public function it_fetches_missing_types_from_wallet_transaction()
    {
        $asset = Event::fakeFor(fn() => WalletTransaction::factory()->create([
            'wallet_transactionable_id' => $this->test_character->character_id
        ]));

        $this->job->handle();

        Queue::assertPushedOn('high', ResolveUniverseTypesByTypeIdJob::class);

        $this->assertTrue(in_array($asset->type_id,cache('type_ids_to_resolve')));
    }

    /** @test */
    public function it_dispatch_resolve_location_jog_for_missing_wallet_transaction_location()
    {
        Event::fakeFor(fn() => WalletTransaction::factory()->create([
            'wallet_transactionable_id' => $this->test_character->character_id
        ]));

        $this->job->handle();

        Queue::assertPushedOn('high', ResolveLocationJob::class);
    }

    /** @test */
    public function it_dispatches_character_info_jog_for_missing_member_tracking_characters()
    {
        Event::fakeFor(fn() => factory(CorporationMemberTracking::class)->create([
            'character_id' => factory(CharacterInfo::class)->make()
        ]));

        $this->job->handle();

        Queue::assertPushedOn('high', CharacterInfoJob::class);
    }

}
