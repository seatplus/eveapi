<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\EsiSchema\Responses\KillmailsKillmailIdKillmailHashGet;
use Seatplus\Eveapi\Jobs\Killmails\KillmailJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseSystemBySystemIdJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Models\Killmails\Killmail;
use Seatplus\Eveapi\Models\Killmails\KillmailAttacker;
use Seatplus\Eveapi\Models\Killmails\KillmailItem;
use Seatplus\Eveapi\Models\Universe\Type;

it('dispatches killmail job', function () {
    Queue::fake();

    KillmailJob::dispatch(123, 'asd');

    Queue::assertPushed(KillmailJob::class);
});

it('creates killmail', function () {
    $killmailData = json_decode(file_get_contents('tests/Stubs/19c919549fb5b4359324fc7938b21f2965f1baf0.json'));
    $dto = KillmailsKillmailIdKillmailHashGet::from($killmailData);
    $dto->isCachedLoad = false;

    mockEsiClient('killmails->getKillmailsKillmailIdKillmailHash', $dto);

    Queue::fake();

    runJob(new KillmailJob(123, 'asd'));

    Queue::assertPushed(ResolveUniverseSystemBySystemIdJob::class);
    Queue::assertPushed(ResolveUniverseTypeByIdJob::class);

    expect(Killmail::all())->toHaveCount(1);

    Event::fakeFor(fn () => Type::factory()->create([
        'type_id' => Killmail::first()->ship_type_id,
    ]));

    $this->assertNotCount(0, Killmail::query()->has('ship')->get());

    $this->assertNotEmpty(KillmailItem::all());
    $this->assertNotCount(0, KillmailItem::where('location_id', 123)->get());

    $this->assertNotCount(0, KillmailItem::query()->has('content')->get());

    Event::fakeFor(fn () => Type::factory()->create([
        'type_id' => KillmailItem::first()->type_id,
    ]));

    $this->assertNotCount(0, KillmailItem::query()->has('type')->get());

    $this->assertNotCount(0, KillmailAttacker::all());
    $this->assertNotCount(0, Killmail::first()->attackers);
    expect(KillmailAttacker::first()->killmail)->toBeInstanceOf(Killmail::class);

    Event::fakeFor(fn () => Type::factory()->createMany([
        ['type_id' => KillmailAttacker::first()->ship_type_id],
    ]));

    expect(KillmailAttacker::first()->weapon)->toBeInstanceOf(Type::class);
    expect(KillmailAttacker::first()->ship)->toBeInstanceOf(Type::class);
});
