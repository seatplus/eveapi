<?php


use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetsNameJob;
use Seatplus\Eveapi\Jobs\Character\CharacterInfoJob;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingAssetsNames;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingBodysFromMails;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingCategorys;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingCharacterInfosFromCorporationMemberTracking;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingConstellations;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingGroups;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingLocationFromAssets;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingLocationFromContracts;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingLocationFromCorporationMemberTracking;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingLocationFromWalletTransaction;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingRegions;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingTypesFromCharacterAssets;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingTypesFromContractItem;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingTypesFromCorporationMemberTracking;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingTypesFromLocations;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingTypesFromSkillQueue;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingTypesFromSkills;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingTypesFromWalletTransaction;
use Seatplus\Eveapi\Jobs\Mail\MailBodyJob;
use Seatplus\Eveapi\Jobs\Seatplus\MaintenanceJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseCategoryByIdJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseConstellationByConstellationIdJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseGroupByIdJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseRegionByRegionIdJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Contracts\Contract;
use Seatplus\Eveapi\Models\Contracts\ContractItem;
use Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking;
use Seatplus\Eveapi\Models\Mail\Mail;
use Seatplus\Eveapi\Models\Mail\MailRecipients;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Skills\Skill;
use Seatplus\Eveapi\Models\Skills\SkillQueue;
use Seatplus\Eveapi\Models\Universe\Constellation;
use Seatplus\Eveapi\Models\Universe\Group;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\System;
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Models\Wallet\WalletTransaction;

beforeEach(function () {
    //$this->job = new MaintenanceJob;
});

it('dispatch get missing types from character assets job', function () {
    Bus::fake();

    (new MaintenanceJob)->handle();

    Bus::assertBatched(fn ($batch) => $batch->jobs->first(fn ($job) => $job instanceof GetMissingTypesFromCharacterAssets));
});

it('fetches missing types from assets', function () {
    $asset = Event::fakeFor(fn () => Asset::factory()->create([
        'assetable_id' => $this->test_character->character_id,
    ]));

    $mock = Mockery::mock(GetMissingTypesFromCharacterAssets::class)->makePartial();

    $mock->shouldReceive('batch->add')
        ->once()
        ->with([
            new ResolveUniverseTypeByIdJob($asset->type_id),
        ]);
    $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();

    $mock->handle();
});

it('dispatch get missing types from locations job', function () {
    Bus::fake();

    (new MaintenanceJob)->handle();

    Bus::assertBatched(fn ($batch) => $batch->jobs->first(fn ($job) => $job instanceof GetMissingTypesFromLocations));
});

it('fetches missing types from locations', function () {
    $station = Event::fakeFor(fn () => Station::factory()->create());
    $location = Event::fakeFor(fn () => Location::factory()->create([
        'location_id' => $station->station_id,
        'locatable_id' => $station->station_id,
        'locatable_type' => Station::class,
    ]));


    $mock = Mockery::mock(GetMissingTypesFromLocations::class)->makePartial();

    $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
    $mock->shouldReceive('batch->add')
        ->once()
        ->with([
            new ResolveUniverseTypeByIdJob($location->locatable->type_id),
        ]);

    $mock->handle();
});

it('dispatch get missing groups job', function () {
    Bus::fake();

    (new MaintenanceJob)->handle();

    Bus::assertBatched(fn ($batch) => $batch->jobs->first(fn ($job) => $job instanceof GetMissingGroups));
});

it('catches missing groups from type', function () {
    $type = Event::fakeFor(fn () => Type::factory()->create());

    $mock = Mockery::mock(GetMissingGroups::class)->makePartial();

    $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
    $mock->shouldReceive('batch->add')
        ->once()
        ->with([
            new ResolveUniverseGroupByIdJob($type->group_id),
        ]);

    $mock->handle();
});

it('dispatch get missing categorys job', function () {
    Bus::fake();

    (new MaintenanceJob)->handle();

    Bus::assertBatched(fn ($batch) => $batch->jobs->first(fn ($job) => $job instanceof GetMissingCategorys));
});

it('catches missing categories from group', function () {
    $group = Event::fakeFor(fn () => Group::factory()->create());

    $mock = Mockery::mock(GetMissingCategorys::class)->makePartial();

    $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
    $mock->shouldReceive('batch->add')
        ->once()
        ->with([
            new ResolveUniverseCategoryByIdJob($group->category_id),
        ]);

    $mock->handle();
});

it('dispatch get missing location from assets job', function () {
    Bus::fake();

    (new MaintenanceJob)->handle();

    Bus::assertBatched(fn ($batch) => $batch->jobs->first(fn ($job) => $job instanceof GetMissingLocationFromAssets));
});

it('adds resolve location job for missing assets location to batch', function () {
    $asset = Event::fakeFor(fn () => Asset::factory()->create([
        'assetable_id' => $this->test_character->character_id,
        'location_flag' => 'Hangar',
    ]));

    $mock = Mockery::mock(GetMissingLocationFromAssets::class)->makePartial();

    $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
    $mock->shouldReceive('batch->add')
        ->once()
        ->with([
            new ResolveLocationJob($asset->location_id, $this->test_character->refresh_token),
        ]);

    $mock->handle();
});

test('get missing location from assets pipe can handle non station or structure locations', function () {
    $type = Type::factory()->create();

    expect(Location::all())->toHaveCount(0);

    $non_structure_or_station_location = Location::factory()->create([
        'location_id' => $type->type_id,
        'locatable_id' => $type->type_id,
        'locatable_type' => Type::class,
    ]);

    expect(Location::all())->toHaveCount(1);

    $asset = Event::fakeFor(fn () => Asset::factory()->create([
        'assetable_id' => $this->test_character->character_id,
        'location_flag' => 'Hangar',
        'location_id' => $type->type_id,
    ]));

    $mock = Mockery::mock(GetMissingLocationFromAssets::class)->makePartial();

    $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
    $mock->shouldReceive('batch->add')
        ->once()
        ->with([
            new ResolveLocationJob($type->type_id, $this->test_character->refresh_token),
        ]);

    $mock->handle();
});

it('dispatch get missing assets names job', function () {
    Bus::fake();

    (new MaintenanceJob)->handle();

    Bus::assertBatched(fn ($batch) => $batch->jobs->first(fn ($job) => $job instanceof GetMissingAssetsNames));
});

it('dispatch resolve missing assets name jog', function () {
    $asset = Event::fakeFor(fn () => Asset::factory()->create([
        'assetable_id' => $this->test_character->character_id,
        'location_flag' => 'Hangar',
    ]));

    $type = Event::fakeFor(fn () => Type::factory()->create([
        'type_id' => $asset->type_id,
        'group_id' => Group::factory()->create(['category_id' => 2]),
    ]));

    $job_container = new JobContainer([
        'refresh_token' => RefreshToken::find($this->test_character->character_id),
    ]);

    $mock = Mockery::mock(GetMissingAssetsNames::class)->makePartial();

    $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
    $mock->shouldReceive('batch->add')
        ->once()
        ->with([
            new CharacterAssetsNameJob($job_container),
        ]);

    $mock->handle();
});

it('dispatch get missing types from corporation member tracking job', function () {
    Bus::fake();

    (new MaintenanceJob)->handle();

    Bus::assertBatched(fn ($batch) => $batch->jobs->first(fn ($job) => $job instanceof GetMissingTypesFromCorporationMemberTracking));
});

it('fetches missing types from corporation member tracking', function () {
    $corporation_member_tracking = Event::fakeFor(fn () => CorporationMemberTracking::factory()->create());


    $mock = Mockery::mock(GetMissingTypesFromCorporationMemberTracking::class)->makePartial();

    $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
    $mock->shouldReceive('batch->add')
        ->once()
        ->with([
            new ResolveUniverseTypeByIdJob($corporation_member_tracking->ship_type_id),
        ]);

    $mock->handle();
});

it('dispatch get missing location from corporation member tracking job', function () {
    Bus::fake();

    (new MaintenanceJob)->handle();

    Bus::assertBatched(fn ($batch) => $batch->jobs->first(fn ($job) => $job instanceof GetMissingLocationFromCorporationMemberTracking));
});

it('dispatch resolve location job for missing corporation member tracking location', function () {
    $corporation_member_tracking = Event::fakeFor(fn () => CorporationMemberTracking::factory()->create([
        'character_id' => $this->test_character->character_id,
    ]));

    $mock = Mockery::mock(GetMissingLocationFromCorporationMemberTracking::class)->makePartial();

    $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
    $mock->shouldReceive('batch->add')
        ->once()
        ->with([
            new ResolveLocationJob($corporation_member_tracking->location_id, $this->test_character->refresh_token),
        ]);

    $mock->handle();
});

test('get missing location from corporation member tracking pipe can handle non station or structure locations', function () {
    $type = Type::factory()->create();

    expect(Location::all())->toHaveCount(0);

    $non_structure_or_station_location = Location::factory()->create([
        'location_id' => $type->type_id,
        'locatable_id' => $type->type_id,
        'locatable_type' => Type::class,
    ]);

    expect(Location::all())->toHaveCount(1);


    Event::fakeFor(fn () => CorporationMemberTracking::factory()->create([
        'character_id' => $this->test_character->character_id,
        'location_id' => $type->type_id,
    ]));

    expect(CorporationMemberTracking::all())->toHaveCount(1);
    $this->assertNotNull(CorporationMemberTracking::first()->location);

    $mock = Mockery::mock(GetMissingLocationFromCorporationMemberTracking::class)->makePartial();

    $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
    $mock->shouldReceive('batch->add')
        ->once()
        ->with([
            new ResolveLocationJob($type->type_id, $this->test_character->refresh_token),
        ]);

    $mock->handle();
});

it('dispatch get missing types from wallet transaction job', function () {
    Bus::fake();

    (new MaintenanceJob)->handle();

    Bus::assertBatched(fn ($batch) => $batch->jobs->first(fn ($job) => $job instanceof GetMissingTypesFromWalletTransaction));
});

it('fetches missing types from wallet transaction', function () {
    $asset = Event::fakeFor(fn () => WalletTransaction::factory()->create([
        'wallet_transactionable_id' => $this->test_character->character_id,
    ]));

    $mock = Mockery::mock(GetMissingTypesFromWalletTransaction::class)->makePartial();

    $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
    $mock->shouldReceive('batch->add')
        ->once()
        ->with([
            new ResolveUniverseTypeByIdJob($asset->type_id),
        ]);

    $mock->handle();
});

it('dispatch get missing location from wallet transaction job', function () {
    Bus::fake();

    (new MaintenanceJob)->handle();

    Bus::assertBatched(fn ($batch) => $batch->jobs->first(fn ($job) => $job instanceof GetMissingLocationFromWalletTransaction));
});

it('dispatch resolve location job for missing wallet transaction location', function () {
    $wallet_transaction = Event::fakeFor(fn () => WalletTransaction::factory()->create([
        'wallet_transactionable_id' => $this->test_character->character_id,
    ]));

    $mock = Mockery::mock(GetMissingLocationFromWalletTransaction::class)->makePartial();

    $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
    $mock->shouldReceive('batch->add')
        ->once()
        ->with([
            new ResolveLocationJob($wallet_transaction->location_id, $this->test_character->refresh_token),
        ]);

    $mock->handle();
});

test('get missing location from wallet transaction pipe can handle non station or structure locations', function () {
    $type = Type::factory()->create();

    expect(Location::all())->toHaveCount(0);

    $non_structure_or_station_location = Location::factory()->create([
        'location_id' => $type->type_id,
        'locatable_id' => $type->type_id,
        'locatable_type' => Type::class,
    ]);

    expect(Location::all())->toHaveCount(1);


    Event::fakeFor(fn () => WalletTransaction::factory()->create([
        'wallet_transactionable_id' => $this->test_character->character_id,
        'location_id' => $type->type_id,
    ]));

    $mock = Mockery::mock(GetMissingLocationFromWalletTransaction::class)->makePartial();

    $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
    $mock->shouldReceive('batch->add')
        ->once()
        ->with([
            new ResolveLocationJob($type->type_id, $this->test_character->refresh_token),
        ]);

    $mock->handle();
});

it('dispatch get missing character infos from corporation member tracking job', function () {
    Bus::fake();

    (new MaintenanceJob)->handle();

    Bus::assertBatched(fn ($batch) => $batch->jobs->first(fn ($job) => $job instanceof GetMissingCharacterInfosFromCorporationMemberTracking));
});

it('dispatches character info job for missing member tracking characters', function () {
    $corporation_member_tracking = Event::fakeFor(fn () => CorporationMemberTracking::factory()->create([
        'character_id' => CharacterInfo::factory()->make(),
    ]));

    $jobContainer = new JobContainer([
        'character_id' => $corporation_member_tracking->character_id,
    ]);

    $mock = Mockery::mock(GetMissingCharacterInfosFromCorporationMemberTracking::class)->makePartial();

    $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
    $mock->shouldReceive('batch->add')
        ->once()
        ->with([
            new CharacterInfoJob($jobContainer),
        ]);

    $mock->handle();
});

it('dispatch get missing types from contract item job', function () {
    Bus::fake();

    (new MaintenanceJob)->handle();

    Bus::assertBatched(fn ($batch) => $batch->jobs->first(fn ($job) => $job instanceof GetMissingTypesFromContractItem));
});

it('dispatches resolve types job for missing contract item types', function () {
    $contract_item = Event::fakeFor(function () {
        $contract = Contract::factory()->create();

        return ContractItem::factory()->withoutType()->create([
            'contract_id' => $contract->contract_id,
        ]);
    });


    $mock = Mockery::mock(GetMissingTypesFromContractItem::class)->makePartial();

    $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
    $mock->shouldReceive('batch->add')
        ->once()
        ->with([
            new ResolveUniverseTypeByIdJob($contract_item->type_id),
        ]);

    $mock->handle();
});

it('dispatch get missing location from contracts job', function () {
    Bus::fake();

    (new MaintenanceJob)->handle();

    Bus::assertBatched(fn ($batch) => $batch->jobs->first(fn ($job) => $job instanceof GetMissingLocationFromContracts));
});

it('dispatches resolve location job for missing contract locations', function () {
    $contract = Event::fakeFor(fn () => Contract::factory()->create([
        'start_location_id' => 12345,
        'end_location_id' => 12345,
        'assignee_id' => $this->test_character->character_id,
    ]));

    $mock = Mockery::mock(GetMissingLocationFromContracts::class)->makePartial();

    $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
    $mock->shouldReceive('batch->add')
        ->once()
        ->with([
            new ResolveLocationJob($contract->start_location_id, $this->test_character->refresh_token),
        ]);

    $mock->handle();
});

test('get missing start location from contracts pipe can handle non station or structure locations', function () {
    $type = Type::factory()->create();

    expect(Location::all())->toHaveCount(0);

    $non_structure_or_station_location = Location::factory()->create([
        'location_id' => $type->type_id,
        'locatable_id' => $type->type_id,
        'locatable_type' => Type::class,
    ]);

    expect(Location::all())->toHaveCount(1);

    $contract = Event::fakeFor(fn () => Contract::factory()->create([
        'start_location_id' => $type->type_id,
        'end_location_id' => $type->type_id,
        'assignee_id' => $this->test_character->character_id,
    ]));

    $mock = Mockery::mock(GetMissingLocationFromContracts::class)->makePartial();

    $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
    $mock->shouldReceive('batch->add')
        ->once()
        ->with([
            new ResolveLocationJob($contract->start_location_id, $this->test_character->refresh_token),
        ]);

    $mock->handle();
});

it('dispatch get missing constellations and get missing regions as chained job', function () {
    Bus::fake();

    (new MaintenanceJob)->handle();

    Bus::assertBatched(fn ($batch) => $batch->jobs->first(fn ($job) => [
        new GetMissingConstellations,
        new GetMissingRegions,
    ])); //$batch->jobs->first(fn($job) => $job instanceof GetMissingConstellations));
});

it('dispatches resolve universe constellation by constellation id job for missing constellations', function () {
    $system = Event::fakeFor(fn () => System::factory()->noConstellation()->create());

    $mock = Mockery::mock(GetMissingConstellations::class)->makePartial();

    $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
    $mock->shouldReceive('batch->add')
        ->once()
        ->with([
            new ResolveUniverseConstellationByConstellationIdJob($system->constellation_id),
        ]);

    $mock->handle();
});

it('dispatches resolve universe region by region id job for missing regions', function () {
    $constellation = Event::fakeFor(fn () => Constellation::factory()->noRegion()->create());

    $mock = Mockery::mock(GetMissingRegions::class)->makePartial();

    $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
    $mock->shouldReceive('batch->add')
        ->once()
        ->with([
            new ResolveUniverseRegionByRegionIdJob($constellation->region_id),
        ]);

    $mock->handle();
});

it('dispatch get missing types from skills as chained job', function () {
    Bus::fake();

    (new MaintenanceJob)->handle();

    Bus::assertBatched(fn ($batch) => $batch->jobs->first(fn ($job) => $job instanceof GetMissingTypesFromSkills));
});

it('dispatches resolve universe type by id job for missing types of skills', function () {
    $skill = Event::fakeFor(fn () => Skill::factory(['skill_id' => 1234])->create());

    $mock = Mockery::mock(GetMissingTypesFromSkills::class)->makePartial();

    $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
    $mock->shouldReceive('batch->add')
        ->once()
        ->with([
            new ResolveUniverseTypeByIdJob($skill->skill_id),
        ]);

    $mock->handle();
});

it('dispatch get missing types from skill queue as chained job', function () {
    Bus::fake();

    (new MaintenanceJob)->handle();

    Bus::assertBatched(fn ($batch) => $batch->jobs->first(fn ($job) => $job instanceof GetMissingTypesFromSkillQueue));
});

it('dispatches resolve universe type by id job for missing types of skillqueue', function () {
    $skill = Event::fakeFor(fn () => SkillQueue::factory(['skill_id' => 1234])->create());

    $mock = Mockery::mock(GetMissingTypesFromSkillQueue::class)->makePartial();

    $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
    $mock->shouldReceive('batch->add')
        ->once()
        ->with([
            new ResolveUniverseTypeByIdJob($skill->skill_id),
        ]);

    $mock->handle();
});

it('dispatch get missing bodys from mails as chained job', function () {
    Bus::fake();

    (new MaintenanceJob)->handle();

    Bus::assertBatched(fn ($batch) => $batch->jobs->first(fn ($job) => $job instanceof GetMissingBodysFromMails));
});

it('dispatches mail body job for missing mail bodies', function () {
    $refresh_token = updateRefreshTokenScopes($this->test_character->refresh_token, ['esi-mail.read_mail.v1']);
    $refresh_token->save();

    $mail = Event::fakeFor(fn () => Mail::factory(['body' => null])->create());

    MailRecipients::create([
        'mail_id' => $mail->id,
        'receivable_id' => $this->test_character->character_id,
        'receivable_type' => CharacterInfo::class,
    ]);

    $mock = Mockery::mock(GetMissingBodysFromMails::class)->makePartial();

    $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
    $mock->shouldReceive('batch->add')
        ->once()
        ->with(Mockery::on(function ($argument) {
            $argumentIsArray = is_array($argument);
            $argumentHasLengthOfOne = count($argument);
            $jobIsMailBodyJob = $argument[0] instanceof MailBodyJob;

            return $argumentIsArray && $argumentHasLengthOfOne && $jobIsMailBodyJob;
        }));

    $mock->handle();
});
