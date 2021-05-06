<?php


namespace Seatplus\Eveapi\Tests\Jobs\Seatplus;


use Illuminate\Bus\Batch;
use Illuminate\Bus\BatchRepository;
use Illuminate\Bus\PendingBatch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetJob;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetsNameDispatchJob;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetsNameJob;
use Seatplus\Eveapi\Jobs\Character\CharacterInfoJob;
use Seatplus\Eveapi\Jobs\Hydrate\Character\CharacterAssetsHydrateBatch;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingAssetsNames;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingCategorys;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingCharacterInfosFromCorporationMemberTracking;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingConstellations;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingGroups;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingRegions;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingLocationFromAssets;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingLocationFromContracts;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingLocationFromCorporationMemberTracking;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingLocationFromWalletTransaction;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingTypesFromCharacterAssets;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingTypesFromContractItem;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingTypesFromCorporationMemberTracking;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingTypesFromLocations;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingTypesFromWalletTransaction;
use Seatplus\Eveapi\Jobs\NewEsiBase;
use Seatplus\Eveapi\Jobs\Seatplus\MaintenanceJob;
use Seatplus\Eveapi\Jobs\Seatplus\UpdateCharacter;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseCategoryByIdJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseConstellationByConstellationIdJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseGroupByIdJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseRegionByRegionIdJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Contracts\Contract;
use Seatplus\Eveapi\Models\Contracts\ContractItem;
use Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Universe\Constellation;
use Seatplus\Eveapi\Models\Universe\Group;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\Structure;
use Seatplus\Eveapi\Models\Universe\System;
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Models\Wallet\WalletTransaction;
use Seatplus\Eveapi\Tests\TestCase;

class MaintenanceJobTest extends TestCase
{

    private MaintenanceJob $job;

    public function setUp(): void
    {

        parent::setUp();

        //$this->job = new MaintenanceJob;

    }

    /** @test */
    public function it_dispatch_GetMissingTypesFromCharacterAssets_job()
    {
        Bus::fake();

        (new MaintenanceJob)->handle();

        Bus::assertBatched(fn($batch) => $batch->jobs->first(fn($job) => $job instanceof GetMissingTypesFromCharacterAssets));
    }

    /** @test */
    public function it_fetches_missing_types_from_assets()
    {

        $asset = Event::fakeFor(fn() => Asset::factory()->create([
            'assetable_id' => $this->test_character->character_id,
        ]));

        $mock = Mockery::mock(GetMissingTypesFromCharacterAssets::class)->makePartial();

        $mock->shouldReceive('batch->add')
            ->once()
            ->with([
                new ResolveUniverseTypeByIdJob($asset->type_id)
            ]);
        $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();

        $mock->handle();
    }

    /** @test */
    public function it_dispatch_GetMissingTypesFromLocations_job()
    {
        Bus::fake();

        (new MaintenanceJob)->handle();

        Bus::assertBatched(fn($batch) => $batch->jobs->first(fn($job) => $job instanceof GetMissingTypesFromLocations));
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


        $mock = Mockery::mock(GetMissingTypesFromLocations::class)->makePartial();

        $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
        $mock->shouldReceive('batch->add')
            ->once()
            ->with([
                new ResolveUniverseTypeByIdJob($location->locatable->type_id)
            ]);

        $mock->handle();
    }

    /** @test */
    public function it_dispatch_GetMissingGroups_job()
    {
        Bus::fake();

        (new MaintenanceJob)->handle();

        Bus::assertBatched(fn($batch) => $batch->jobs->first(fn($job) => $job instanceof GetMissingGroups));
    }

    /** @test */
    public function it_catches_missing_groups_from_type()
    {
        $type = Event::fakeFor(fn() => Type::factory()->create());

        $mock = Mockery::mock(GetMissingGroups::class)->makePartial();

        $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
        $mock->shouldReceive('batch->add')
            ->once()
            ->with([
                new ResolveUniverseGroupByIdJob($type->group_id)
            ]);

        $mock->handle();
    }

    /** @test */
    public function it_dispatch_GetMissingCategorys_job()
    {
        Bus::fake();

        (new MaintenanceJob)->handle();

        Bus::assertBatched(fn($batch) => $batch->jobs->first(fn($job) => $job instanceof GetMissingCategorys));
    }

    /** @test */
    public function it_catches_missing_categories_from_group()
    {
        $group = Event::fakeFor(fn() => Group::factory()->create());

        $mock = Mockery::mock(GetMissingCategorys::class)->makePartial();

        $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
        $mock->shouldReceive('batch->add')
            ->once()
            ->with([
                new ResolveUniverseCategoryByIdJob($group->category_id)
            ]);

        $mock->handle();
    }

    /** @test */
    public function it_dispatch_GetMissingLocationFromAssets_job()
    {
        Bus::fake();

        (new MaintenanceJob)->handle();

        Bus::assertBatched(fn($batch) => $batch->jobs->first(fn($job) => $job instanceof GetMissingLocationFromAssets));
    }

    /** @test */
    public function it_adds_ResolveLocationJob_for_missing_assets_location_to_batch()
    {
        $asset = Event::fakeFor(fn() => Asset::factory()->create([
            'assetable_id' => $this->test_character->character_id,
            'location_flag' => 'Hangar'
        ]));

        $mock = Mockery::mock(GetMissingLocationFromAssets::class)->makePartial();

        $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
        $mock->shouldReceive('batch->add')
            ->once()
            ->with([
                new ResolveLocationJob($asset->location_id, $this->test_character->refresh_token)
            ]);

        $mock->handle();
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

        $mock = Mockery::mock(GetMissingLocationFromAssets::class)->makePartial();

        $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
        $mock->shouldReceive('batch->add')
            ->once()
            ->with([
                new ResolveLocationJob($type->type_id, $this->test_character->refresh_token)
            ]);

        $mock->handle();
    }

    /** @test */
    public function it_dispatch_GetMissingAssetsNames_job()
    {
        Bus::fake();

        (new MaintenanceJob)->handle();

        Bus::assertBatched(fn($batch) => $batch->jobs->first(fn($job) => $job instanceof GetMissingAssetsNames));
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

        $job_container = new JobContainer([
            'refresh_token' => RefreshToken::find($this->test_character->character_id),
        ]);

        $mock = Mockery::mock(GetMissingAssetsNames::class)->makePartial();

        $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
        $mock->shouldReceive('batch->add')
            ->once()
            ->with([
                new CharacterAssetsNameDispatchJob($job_container)
            ]);

        $mock->handle();
    }

    /** @test */
    public function it_dispatch_GetMissingTypesFromCorporationMemberTracking_job()
    {
        Bus::fake();

        (new MaintenanceJob)->handle();

        Bus::assertBatched(fn($batch) => $batch->jobs->first(fn($job) => $job instanceof GetMissingTypesFromCorporationMemberTracking));
    }

    /** @test */
    public function it_fetches_missing_types_from_corporation_member_tracking()
    {
        $corporation_member_tracking = Event::fakeFor(fn() => CorporationMemberTracking::factory()->create());


        $mock = Mockery::mock(GetMissingTypesFromCorporationMemberTracking::class)->makePartial();

        $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
        $mock->shouldReceive('batch->add')
            ->once()
            ->with([
                new ResolveUniverseTypeByIdJob($corporation_member_tracking->ship_type_id)
            ]);

        $mock->handle();
    }

    /** @test */
    public function it_dispatch_GetMissingLocationFromCorporationMemberTracking_job()
    {
        Bus::fake();

        (new MaintenanceJob)->handle();

        Bus::assertBatched(fn($batch) => $batch->jobs->first(fn($job) => $job instanceof GetMissingLocationFromCorporationMemberTracking));
    }

    /** @test */
    public function it_dispatch_resolve_location_job_for_missing_corporation_member_tracking_location()
    {
        $corporation_member_tracking = Event::fakeFor(fn() => CorporationMemberTracking::factory()->create([
            'character_id' => $this->test_character->character_id,
        ]));

        $mock = Mockery::mock(GetMissingLocationFromCorporationMemberTracking::class)->makePartial();

        $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
        $mock->shouldReceive('batch->add')
            ->once()
            ->with([
                new ResolveLocationJob($corporation_member_tracking->location_id, $this->test_character->refresh_token)
            ]);

        $mock->handle();
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

        $mock = Mockery::mock(GetMissingLocationFromCorporationMemberTracking::class)->makePartial();

        $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
        $mock->shouldReceive('batch->add')
            ->once()
            ->with([
                new ResolveLocationJob($type->type_id, $this->test_character->refresh_token)
            ]);

        $mock->handle();
    }

    /** @test */
    public function it_dispatch_GetMissingTypesFromWalletTransaction_job()
    {
        Bus::fake();

        (new MaintenanceJob)->handle();

        Bus::assertBatched(fn($batch) => $batch->jobs->first(fn($job) => $job instanceof GetMissingTypesFromWalletTransaction));
    }

    /** @test */
    public function it_fetches_missing_types_from_wallet_transaction()
    {
        $asset = Event::fakeFor(fn() => WalletTransaction::factory()->create([
            'wallet_transactionable_id' => $this->test_character->character_id
        ]));

        $mock = Mockery::mock(GetMissingTypesFromWalletTransaction::class)->makePartial();

        $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
        $mock->shouldReceive('batch->add')
            ->once()
            ->with([
                new ResolveUniverseTypeByIdJob($asset->type_id)
            ]);

        $mock->handle();
    }

    /** @test */
    public function it_dispatch_GetMissingLocationFromWalletTransaction_job()
    {
        Bus::fake();

        (new MaintenanceJob)->handle();

        Bus::assertBatched(fn($batch) => $batch->jobs->first(fn($job) => $job instanceof GetMissingLocationFromWalletTransaction));
    }

    /** @test */
    public function it_dispatch_resolve_location_job_for_missing_wallet_transaction_location()
    {
        $wallet_transaction = Event::fakeFor(fn() => WalletTransaction::factory()->create([
            'wallet_transactionable_id' => $this->test_character->character_id
        ]));

        $mock = Mockery::mock(GetMissingLocationFromWalletTransaction::class)->makePartial();

        $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
        $mock->shouldReceive('batch->add')
            ->once()
            ->with([
                new ResolveLocationJob($wallet_transaction->location_id, $this->test_character->refresh_token)
            ]);

        $mock->handle();
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

        $mock = Mockery::mock(GetMissingLocationFromWalletTransaction::class)->makePartial();

        $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
        $mock->shouldReceive('batch->add')
            ->once()
            ->with([
                new ResolveLocationJob($type->type_id, $this->test_character->refresh_token)
            ]);

        $mock->handle();
    }

    /** @test */
    public function it_dispatch_GetMissingCharacterInfosFromCorporationMemberTracking_job()
    {
        Bus::fake();

        (new MaintenanceJob)->handle();

        Bus::assertBatched(fn($batch) => $batch->jobs->first(fn($job) => $job instanceof GetMissingCharacterInfosFromCorporationMemberTracking));
    }

    /** @test */
    public function it_dispatches_character_info_job_for_missing_member_tracking_characters()
    {
        $corporation_member_tracking = Event::fakeFor(fn() => CorporationMemberTracking::factory()->create([
            'character_id' => CharacterInfo::factory()->make()
        ]));

        $jobContainer = new JobContainer([
            'character_id' => $corporation_member_tracking->character_id,
        ]);

        $mock = Mockery::mock(GetMissingCharacterInfosFromCorporationMemberTracking::class)->makePartial();

        $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
        $mock->shouldReceive('batch->add')
            ->once()
            ->with([
                new CharacterInfoJob($jobContainer)
            ]);

        $mock->handle();
    }

    /** @test */
    public function it_dispatch_GetMissingTypesFromContractItem_job()
    {
        Bus::fake();

        (new MaintenanceJob)->handle();

        Bus::assertBatched(fn($batch) => $batch->jobs->first(fn($job) => $job instanceof GetMissingTypesFromContractItem));
    }

    /** @test */
    public function it_dispatches_resolve_types_job_for_missing_contract_item_types()
    {
        $contract_item = Event::fakeFor(fn() => ContractItem::factory()->withoutType()->create());

        $mock = Mockery::mock(GetMissingTypesFromContractItem::class)->makePartial();

        $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
        $mock->shouldReceive('batch->add')
            ->once()
            ->with([
                new ResolveUniverseTypeByIdJob($contract_item->type_id)
            ]);

        $mock->handle();
    }

    /** @test */
    public function it_dispatch_GetMissingLocationFromContracts_job()
    {
        Bus::fake();

        (new MaintenanceJob)->handle();

        Bus::assertBatched(fn($batch) => $batch->jobs->first(fn($job) => $job instanceof GetMissingLocationFromContracts));
    }

    /** @test */
    public function it_dispatches_resolve_location_job_for_missing_contract_locations()
    {
        $contract = Event::fakeFor(fn() => Contract::factory()->create([
            'start_location_id' => 12345,
            'end_location_id' => 12345,
            'assignee_id' => $this->test_character->character_id
        ]));

        $mock = Mockery::mock(GetMissingLocationFromContracts::class)->makePartial();

        $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
        $mock->shouldReceive('batch->add')
            ->once()
            ->with([
                new ResolveLocationJob($contract->start_location_id, $this->test_character->refresh_token)
            ]);

        $mock->handle();

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
            'end_location_id' => $type->type_id,
            'assignee_id' => $this->test_character->character_id
        ]));

        $mock = Mockery::mock(GetMissingLocationFromContracts::class)->makePartial();

        $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
        $mock->shouldReceive('batch->add')
            ->once()
            ->with([
                new ResolveLocationJob($contract->start_location_id, $this->test_character->refresh_token)
            ]);

        $mock->handle();
    }

    /** @test */
    public function it_dispatch_GetMissingConstellations_and_GetMissingRegions_asChained_job()
    {
        Bus::fake();

        (new MaintenanceJob)->handle();

        Bus::assertBatched(fn($batch) => $batch->jobs->first(fn($job) => [
            new GetMissingConstellations,
            new GetMissingRegions,
        ])); //$batch->jobs->first(fn($job) => $job instanceof GetMissingConstellations));
    }

    /** @test */
    public function it_dispatches_ResolveUniverseConstellationByConstellationIdJob_for_missing_constellations()
    {
        $system = Event::fakeFor(fn() => System::factory()->noConstellation()->create());

        $mock = Mockery::mock(GetMissingConstellations::class)->makePartial();

        $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
        $mock->shouldReceive('batch->add')
            ->once()
            ->with([
                new ResolveUniverseConstellationByConstellationIdJob($system->constellation_id)
            ]);

        $mock->handle();

    }

    /** @test */
    public function it_dispatches_ResolveUniverseRegionByRegionIdJob_for_missing_constellations()
    {
        $constellation = Event::fakeFor(fn() => Constellation::factory()->noRegion()->create());

        $mock = Mockery::mock(GetMissingRegions::class)->makePartial();

        $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
        $mock->shouldReceive('batch->add')
            ->once()
            ->with([
                new ResolveUniverseRegionByRegionIdJob($constellation->region_id)
            ]);

        $mock->handle();

    }

}
