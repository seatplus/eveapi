<?php


namespace Seatplus\Eveapi\Tests\Integration;


use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seat\Eseye\Containers\EsiResponse;
use Seatplus\Eveapi\Jobs\Killmails\KillmailJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseSystemBySystemIdJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Models\Contacts\Label;
use Seatplus\Eveapi\Models\Killmails\Killmail;
use Seatplus\Eveapi\Models\Killmails\KillmailAttacker;
use Seatplus\Eveapi\Models\Killmails\KillmailItem;
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Services\Facade\RetrieveEsiData;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

class KillmailsIntegrationJobTest extends TestCase
{
    use MockRetrieveEsiDataAction;

    public function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub

    }

    /** @test */
    public function itDispatchesKillmailJob() {

        Queue::fake();

        KillmailJob::dispatch(123, 'asd');

        Queue::assertPushed(KillmailJob::class);

    }

    /** @test */
    public function itRunsTheJob()
    {

        RetrieveEsiData::shouldReceive('execute');

        KillmailJob::dispatch(123, 'asd');

    }

    /** @test */
    public function itCreatesKillmail()
    {
        $this->buildMockEsiData();

        Queue::fake();

        (new KillmailJob(123, 'asd'))->handle();

        Queue::assertPushed(ResolveUniverseSystemBySystemIdJob::class);
        Queue::assertPushed(ResolveUniverseTypeByIdJob::class);


        $this->assertCount(1, Killmail::all());

        // it has ship type
        Event::fakeFor(fn () => Type::factory()->create([
            'type_id' => Killmail::first()->ship_type_id
        ]));

        $this->assertNotCount(0, Killmail::query()->has('ship')->get());

        // it creates Killmail Items
        $this->assertNotEmpty(KillmailItem::all());
        $this->assertNotCount(0, KillmailItem::where('location_id', 123)->get());

        // killmail item should have a content relationship
        $this->assertNotCount(0, KillmailItem::query()->has('content')->get());

        // killmail item should have a type relationship
        Event::fakeFor(fn () => Type::factory()->create([
            'type_id' => KillmailItem::first()->type_id
        ]));

        $this->assertNotCount(0, KillmailItem::query()->has('type')->get());

        // it creates Killmail Attackers
        $this->assertNotCount(0, KillmailAttacker::all());
        $this->assertNotCount(0, Killmail::first()->attackers);
        $this->assertInstanceOf(Killmail::class, KillmailAttacker::first()->killmail);

        // killmail item should have a type relationship
        Event::fakeFor(fn () => Type::factory()->createMany([
            [
                'type_id' => KillmailAttacker::first()->ship_type_id
            ]
        ]));

        $this->assertInstanceOf(Type::class, KillmailAttacker::first()->weapon);
        $this->assertInstanceOf(Type::class, KillmailAttacker::first()->ship);

    }



    private function buildMockEsiData()
    {

        $killmail = file_get_contents('tests/Stubs/19c919549fb5b4359324fc7938b21f2965f1baf0.json');

        $response = new EsiResponse($killmail, [], 'now', 200);

        RetrieveEsiData::shouldReceive('execute')
            ->once()
            ->andReturn($response);

    }

}