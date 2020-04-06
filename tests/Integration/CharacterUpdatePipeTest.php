<?php


namespace Seatplus\Eveapi\Tests\Integration;


use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetJob;
use Seatplus\Eveapi\Jobs\Character\CharacterInfo as CharacterInfoJob;
use Seatplus\Eveapi\Jobs\Seatplus\UpdateCharacters;
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

        (new UpdateCharacters)->handle();

        Bus::assertDispatched(CharacterAssetJob::class, function ($job) use ($refresh_token) {
            return $refresh_token->character_id === $job->character_id;
        });
    }

    /** @test */
    public function it_dispatches_character_info()
    {
        Bus::fake();

        (new UpdateCharacters)->handle();

        Bus::assertDispatched(CharacterInfoJob::class, function ($job) {
            return $this->test_character->refresh_token->character_id === $job->refresh_token->character_id;
        });
    }

    /** @test */
    public function it_does_not_dispatch_asset_job_for_missing_scopes()
    {
        Bus::fake();

        (new UpdateCharacters)->handle();

        Bus::assertNotDispatched(CharacterAssetJob::class);
    }

}
