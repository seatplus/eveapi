<?php


namespace Seatplus\Eveapi\Tests\Integration;


use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Events\RefreshTokenCreated;
use Seatplus\Eveapi\Jobs\Seatplus\UpdateCharacter;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Tests\TestCase;

class NewRefreshTokenTest extends TestCase
{
    /** @test */
    public function it_generates_an_event()
    {
        Event::fake();

        $refresh_token = factory(RefreshToken::class)->create();

        Event::assertDispatched(RefreshTokenCreated::class, function ($e) use ($refresh_token) {
            return $e->refresh_token === $refresh_token;
        });
    }

    /** @test */
    public function it_queues_update_character_job()
    {
        Queue::fake();

        $refresh_token = factory(RefreshToken::class)->create();

        Queue::assertPushedOn('high', UpdateCharacter::class);
    }
}
