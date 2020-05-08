<?php


namespace Seatplus\Eveapi\Tests\Integration;


use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Events\RefreshTokenCreated;
use Seatplus\Eveapi\Jobs\Seatplus\UpdateCharacter;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Tests\TestCase;

class RefreshTokenLifeCycleTest extends TestCase
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

    /** @test */
    public function it_queues_update_Character_job_after_scope_change()
    {
        $refresh_token = Event::fakeFor( function () {
            return factory(RefreshToken::class)->create([
                'scopes' => ['esi-assets.read_assets.v1', 'esi-universe.read_structures.v1']
            ]);
        });

        Queue::fake();

        $refresh_token->scopes = ['updating'];
        $refresh_token->save();

        Queue::assertPushedOn('high', UpdateCharacter::class);
    }
}
