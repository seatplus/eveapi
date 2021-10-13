<?php


use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Tests\TestCase;



beforeEach(function () {
    Queue::fake();
});

it('dispatches type job', function () {
    $asset = Asset::factory()->make();

    Queue::assertNotPushed('high', ResolveUniverseTypeByIdJob::class);

    $this->assertDatabaseMissing('assets', ['item_id' => $asset->item_id]);

    Asset::updateOrCreate([
        'item_id' => $asset->item_id,
    ], [
        'assetable_id' => $asset->assetable_id,
        'assetable_type' => CharacterInfo::class,
        'is_blueprint_copy' => optional($asset)->is_blueprint_copy ?? false,
        'is_singleton' => $asset->is_singleton,
        'location_flag' => $asset->location_flag,
        'location_id' => $asset->location_id,
        'location_type' => $asset->location_type,
        'quantity' => $asset->quantity,
        'type_id' => $asset->type_id,
    ]);

    $this->assertDatabaseHas('assets', ['item_id' => $asset->item_id]);

    Queue::assertPushedOn('high', ResolveUniverseTypeByIdJob::class);

    Queue::assertPushed(ResolveUniverseTypeByIdJob::class, function ($job) use ($asset) {
        return in_array(sprintf('type_id:%s', $asset->type_id), $job->tags());
    });
});

it('does not dispatch type job if type is known', function () {
    $type = Type::factory()->create();

    $asset = Asset::factory()->create([
        'type_id' => $type->type_id,
    ]);

    Queue::assertNotPushed(ResolveUniverseTypeByIdJob::class);
});

it('dispatches location job', function () {
    $asset = Asset::factory()->create([
        'assetable_id' => $this->test_character->character_id,
    ]);

    Queue::assertPushedOn('high', ResolveLocationJob::class);
});

it('does not dispatch location job if location is known', function () {
    $location = Location::factory()->create();

    $asset = Asset::factory()->create([
        'location_id' => $location->location_id,
    ]);

    Queue::assertNotPushed(ResolveLocationJob::class);
});

it('dispatches location job if location is updating', function () {
    $asset = Event::fakeFor(function () {
        return Asset::factory()->create([
            'location_id' => 1234,
            'assetable_id' => $this->test_character->character_id,
        ]);
    });

    Queue::assertNotPushed(ResolveLocationJob::class);

    $asset->location_id = 56789;
    $asset->save();

    Queue::assertPushedOn('high', ResolveLocationJob::class);
});
