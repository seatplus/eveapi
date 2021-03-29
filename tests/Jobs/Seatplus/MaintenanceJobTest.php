<?php


namespace Seatplus\Eveapi\Tests\Jobs\Seatplus;


use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetsNameJob;
use Seatplus\Eveapi\Jobs\Character\CharacterInfoJob;
use Seatplus\Eveapi\Jobs\Seatplus\MaintenanceJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseCategoryByIdJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseGroupByIdJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Contracts\Contract;
use Seatplus\Eveapi\Models\Contracts\ContractItem;
use Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking;
use Seatplus\Eveapi\Models\Universe\Group;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Models\Wallet\WalletTransaction;
use Seatplus\Eveapi\Tests\TestCase;

class MaintenanceJobTest extends TestCase
{

    private MaintenanceJob $job;

    public function setUp(): void
    {

        parent::setUp();

        $this->job = new MaintenanceJob;

    }

    /** @test */
    public function it_fetches_missing_types_from_assets()
    {
        $asset = Event::fakeFor(fn() => Asset::factory()->create([
            'assetable_id' => $this->test_character->character_id,
        ]));

        $this->job->handle();

        Queue::assertPushedOn('high', ResolveUniverseTypeByIdJob::class);
    }

    /** @test */
    public function it_fetches_missing_types_from_locations()
    {

        $station = Event::fakeFor(fn() => Station::factory()->create());
        $location = Event::fakeFor(fn() => Location::factory()->create([
            'location_id' => $station->station_id,
            'locatable_id' => $station->station_id,
            'locatable_type' => Station::class,
        ]));

        $this->job->handle();

        Queue::assertPushedOn('high', ResolveUniverseTypeByIdJob::class);
    }

    /** @test */
    public function it_caches_missing_groups_from_type()
    {
        $type = Event::fakeFor(fn() => Type::factory()->create());

        $this->job->handle();

        Queue::assertPushedOn('high', ResolveUniverseGroupByIdJob::class);
    }

    /** @test */
    public function it_caches_missing_categories_from_group()
    {
        $group = Event::fakeFor(fn() => Group::factory()->create());

        $this->job->handle();

        Queue::assertPushedOn('high', ResolveUniverseCategoryByIdJob::class);
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
            'group_id' => Group::factory()->create(['category_id' => 2])
        ]));

        $this->job->handle();

        Queue::assertPushedOn('high', CharacterAssetsNameJob::class);
    }

    /** @test */
    public function it_dispatch_resolve_location_job_for_missing_corporation_member_tracking_location()
    {
        Event::fakeFor(fn() => CorporationMemberTracking::factory()->create([
            'character_id' => $this->test_character->character_id,
        ]));

        $this->job->handle();

        Queue::assertPushedOn('high', ResolveLocationJob::class);
    }

    /** @test */
    public function getMissingLocationFromCorporationMemberTrackingPipeCanHandleNonStationOrStructureLocations()
    {

        $type = Type::factory()->create();

        $this->assertCount(0, Location::all());

        $non_structure_or_station_location = Location::factory()->create([
            'location_id' => $type->type_id,
            'locatable_id' => $type->type_id,
            'locatable_type' => Type::class
        ]);

        $this->assertCount(1, Location::all());


        Event::fakeFor(fn() => CorporationMemberTracking::factory()->create([
            'character_id' => $this->test_character->character_id,
            'location_id' => $type->type_id
        ]));

        $this->assertCount(1, CorporationMemberTracking::all());
        $this->assertNotNull(CorporationMemberTracking::first()->location);

        $this->job->handle();

        Queue::assertPushedOn('high', ResolveLocationJob::class);
    }

    /** @test */
    public function it_fetches_missing_types_from_corporation_member_tracking()
    {
        $corporation_member_tracking = Event::fakeFor(fn() => CorporationMemberTracking::factory()->create());

        $this->job->handle();

        Queue::assertPushedOn('high', ResolveUniverseTypeByIdJob::class);
    }

    /** @test */
    public function it_fetches_missing_types_from_wallet_transaction()
    {
        $asset = Event::fakeFor(fn() => WalletTransaction::factory()->create([
            'wallet_transactionable_id' => $this->test_character->character_id
        ]));

        $this->job->handle();

        Queue::assertPushedOn('high', ResolveUniverseTypeByIdJob::class);
    }

    /** @test */
    public function it_dispatch_resolve_location_job_for_missing_wallet_transaction_location()
    {
        Event::fakeFor(fn() => WalletTransaction::factory()->create([
            'wallet_transactionable_id' => $this->test_character->character_id
        ]));

        $this->job->handle();

        Queue::assertPushedOn('high', ResolveLocationJob::class);
    }

    /** @test */
    public function getMissingLocationFromWalletTransactionPipeCanHandleNonStationOrStructureLocations()
    {
        $type = Type::factory()->create();

        $this->assertCount(0, Location::all());

        $non_structure_or_station_location = Location::factory()->create([
            'location_id' => $type->type_id,
            'locatable_id' => $type->type_id,
            'locatable_type' => Type::class
        ]);

        $this->assertCount(1, Location::all());


        Event::fakeFor(fn() => WalletTransaction::factory()->create([
            'wallet_transactionable_id' => $this->test_character->character_id,
            'location_id' => $type->type_id
        ]));

        $this->job->handle();

        Queue::assertPushedOn('high', ResolveLocationJob::class);
    }

    /** @test */
    public function it_dispatches_character_info_job_for_missing_member_tracking_characters()
    {
        Event::fakeFor(fn() => CorporationMemberTracking::factory()->create([
            'character_id' => CharacterInfo::factory()->make()
        ]));

        $this->job->handle();

        Queue::assertPushedOn('high', CharacterInfoJob::class);
    }

    /** @test */
    public function it_dispatches_resolve_location_job_for_missing_contract_locations()
    {
        Event::fakeFor(fn() => Contract::factory()->create([
            'start_location_id' => 12345,
            'end_location_id' => 12345,
            'assignee_id' => $this->test_character->character_id
        ]));

        $this->job->handle();

        Queue::assertPushedOn('high', ResolveLocationJob::class);
    }

    /** @test */
    public function it_dispatches_resolve_types_job_for_missing_contract_item_types()
    {
        $contract_item = Event::fakeFor(fn() => ContractItem::factory()->withoutType()->create());

        $this->job->handle();

        Queue::assertPushedOn('high', ResolveUniverseTypeByIdJob::class);
    }

    /** @test */
    public function getMissingLocationFromAssetsPipeCanHandleNonStationOrStructureLocations()
    {
        $type = Type::factory()->create();

        $this->assertCount(0, Location::all());

        $non_structure_or_station_location = Location::factory()->create([
            'location_id' => $type->type_id,
            'locatable_id' => $type->type_id,
            'locatable_type' => Type::class
        ]);

        $this->assertCount(1, Location::all());

        $asset = Event::fakeFor(fn() => Asset::factory()->create([
            'assetable_id' => $this->test_character->character_id,
            'location_flag' => 'Hangar',
            'location_id' => $type->type_id
        ]));

        $this->job->handle();

        Queue::assertPushedOn('high', ResolveLocationJob::class);
    }

    /** @test */
    public function getMissingStartLocationFromContractsPipeCanHandleNonStationOrStructureLocations()
    {
        $type = Type::factory()->create();

        $this->assertCount(0, Location::all());

        $non_structure_or_station_location = Location::factory()->create([
            'location_id' => $type->type_id,
            'locatable_id' => $type->type_id,
            'locatable_type' => Type::class
        ]);

        $this->assertCount(1, Location::all());

        $contract = Event::fakeFor(fn() => Contract::factory()->create([
            'start_location_id' => $type->type_id,
            'assignee_id' => $this->test_character->character_id
        ]));

        $this->job->handle();

        Queue::assertPushedOn('high', ResolveLocationJob::class);
    }

    /** @test */
    public function getMissingEndLocationFromContractsPipeCanHandleNonStationOrStructureLocations()
    {
        $type = Type::factory()->create();

        $this->assertCount(0, Location::all());

        $non_structure_or_station_location = Location::factory()->create([
            'location_id' => $type->type_id,
            'locatable_id' => $type->type_id,
            'locatable_type' => Type::class
        ]);

        $this->assertCount(1, Location::all());

        $contract = Event::fakeFor(fn() => Contract::factory()->create([
            'end_location_id' => $type->type_id,
            'assignee_id' => $this->test_character->character_id
        ]));

        $this->job->handle();

        Queue::assertPushedOn('high', ResolveLocationJob::class);
    }

}
