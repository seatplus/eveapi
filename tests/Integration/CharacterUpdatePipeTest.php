<?php


namespace Seatplus\Eveapi\Tests\Integration;


use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetJob;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetsNameJob;
use Seatplus\Eveapi\Jobs\Character\CharacterInfo as CharacterInfoJob;
use Seatplus\Eveapi\Jobs\Character\CharacterRoleJob;
use Seatplus\Eveapi\Jobs\Seatplus\UpdateCharacter;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Tests\TestCase;

class CharacterUpdatePipeTest extends TestCase
{
    /** @test */
    public function it_dispatches_character_assets()
    {
        $refresh_token = Event::fakeFor( function () {
            return factory(RefreshToken::class)->create([
                'scopes' => ['esi-assets.read_assets.v1', 'esi-universe.read_structures.v1']
            ]);
        });

        Bus::fake();

        (new UpdateCharacter)->handle();

        Bus::assertDispatched(CharacterAssetJob::class, function ($job) use ($refresh_token) {
            return $refresh_token->character_id === $job->character_id;
        });
    }

    /** @test */
    public function it_dispatches_character_info()
    {
        Bus::fake();

        (new UpdateCharacter)->handle();

        Bus::assertDispatched(CharacterInfoJob::class, function ($job) {
            return $this->test_character->refresh_token->character_id === $job->refresh_token->character_id;
        });
    }

    /** @test */
    public function it_does_not_dispatch_asset_job_for_missing_scopes()
    {
        Bus::fake();

        (new UpdateCharacter)->handle();

        Bus::assertNotDispatched(CharacterAssetJob::class);
    }

    /** @test */
    public function if_constructor_receives_single_refresh_token_push_update_to_high_queue()
    {
        $refresh_token = Event::fakeFor( function () {
            return factory(RefreshToken::class)->create([
                'scopes' => ['esi-assets.read_assets.v1', 'esi-universe.read_structures.v1']
            ]);
        });

        Queue::fake();

        (new UpdateCharacter($refresh_token))->handle();

        Queue::assertPushedOn('high', CharacterInfoJob::class);

        Queue::assertPushed(CharacterInfoJob::class, function ($job) use ($refresh_token){

            return $refresh_token->character_id === $job->refresh_token->character_id;
        });
    }

    /** @test */
    public function it_dispatches_name_job_as_chain()
    {
        $refresh_token = Event::fakeFor( function () {
            return factory(RefreshToken::class)->create([
                'scopes' => ['esi-assets.read_assets.v1', 'esi-universe.read_structures.v1']
            ]);
        });

        Queue::fake();

        (new UpdateCharacter)->handle();

        Queue::assertPushedWithChain(CharacterAssetJob::class, [
            CharacterAssetsNameJob::class
        ]);
    }

    /** @test */
    public function it_dispatches_character_role_job()
    {
        $refresh_token = Event::fakeFor( function () {
            return factory(RefreshToken::class)->create([
                'scopes' => ['esi-characters.read_corporation_roles.v1']
            ]);
        });

        Queue::fake();

        (new UpdateCharacter($refresh_token))->handle();

        Queue::assertPushedOn('high', CharacterRoleJob::class);

        Queue::assertPushed(CharacterRoleJob::class, function ($job) use ($refresh_token){

            return $refresh_token->character_id === $job->refresh_token->character_id;
        });
    }

}
