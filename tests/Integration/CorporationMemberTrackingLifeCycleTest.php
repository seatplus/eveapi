<?php


use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Character\CharacterInfoJob as CharacterInfoJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Queue::fake();
});

it('dispatches type job', function () {
    $tracking = CorporationMemberTracking::factory()->make([
        'ship_type_id' => Type::factory()->make(),
    ]);

    Queue::assertNotPushed('high', ResolveUniverseTypeByIdJob::class);

    $this->assertDatabaseMissing('corporation_member_trackings', ['ship_type_id' => $tracking->ship_type_id]);

    $tracking->save();

    $this->assertDatabaseHas('corporation_member_trackings', ['ship_type_id' => $tracking->ship_type_id]);

    Queue::assertPushedOn('high', ResolveUniverseTypeByIdJob::class);

    Queue::assertPushed(ResolveUniverseTypeByIdJob::class, function ($job) use ($tracking) {
        return in_array(sprintf('type_id:%s', $tracking->ship_type_id), $job->tags());
    });
});

it('does not dispatch type job if type is known', function () {
    $type = Type::factory()->create();

    $tracking = CorporationMemberTracking::factory()->create([
        'ship_type_id' => $type->type_id,
    ]);

    Queue::assertNotPushed(ResolveUniverseTypeByIdJob::class);
});

it('dispatches location job', function () {
    $tracking = CorporationMemberTracking::factory()->create([
        'character_id' => $this->test_character->character_id,
    ]);

    Queue::assertPushedOn('high', ResolveLocationJob::class);
});

it('does not dispatch location job if location is known', function () {
    $location = Location::factory()->create();

    $tracking = CorporationMemberTracking::factory()->create([
        'location_id' => $location->location_id,
    ]);

    Queue::assertNotPushed(ResolveLocationJob::class);
});

it('dispatches location job if location is updating', function () {
    $tracking = Event::fakeFor(function () {
        return CorporationMemberTracking::factory()->create([
            'location_id' => 1234,
        ]);
    });

    Queue::assertNotPushed(ResolveLocationJob::class);

    $tracking->location_id = 56789;
    $tracking->save();

    Queue::assertPushedOn('high', ResolveLocationJob::class);
});

it('dispatches type job if ship is updating', function () {
    $tracking = Event::fakeFor(function () {
        return CorporationMemberTracking::factory()->create([
            'ship_type_id' => 1234,
        ]);
    });

    Queue::assertNotPushed(ResolveUniverseTypeByIdJob::class);

    $tracking->ship_type_id = 56789;
    $tracking->save();

    Queue::assertPushedOn('high', ResolveUniverseTypeByIdJob::class);
});

it('does not dispatch character job if character is known', function () {
    $character = CharacterInfo::factory()->create();

    $tracking = CorporationMemberTracking::factory()->create([
        'character_id' => $character->character_id,
    ]);

    Queue::assertNotPushed(CharacterInfoJob::class);
});
