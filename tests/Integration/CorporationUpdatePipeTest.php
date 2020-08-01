<?php


namespace Seatplus\Eveapi\Tests\Integration;


use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetJob;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetsNameJob;
use Seatplus\Eveapi\Jobs\Character\CharacterInfo as CharacterInfoJob;
use Seatplus\Eveapi\Jobs\Character\CharacterRoleJob;
use Seatplus\Eveapi\Jobs\Corporation\CorporationMemberTrackingJob;
use Seatplus\Eveapi\Jobs\Seatplus\UpdateCharacter;
use Seatplus\Eveapi\Jobs\Seatplus\UpdateCorporation;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;
use Seatplus\Eveapi\Models\Character\CharacterRole;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Tests\TestCase;

class CorporationUpdatePipeTest extends TestCase
{
    /** @test */
    public function it_dispatches_corporation_member_tracking_as_non_director()
    {
        $refresh_token = Event::fakeFor( function () {
            return factory(RefreshToken::class)->create([
                'scopes' => ['esi-corporations.track_members.v1']
            ]);
        });

        factory(CharacterAffiliation::class)->create([
            'character_id' => $refresh_token->character_id,
            'corporation_id' => factory(CorporationInfo::class)
        ]);

        Bus::fake();

        (new UpdateCorporation())->handle();

        Bus::assertDispatched(CorporationMemberTrackingJob::class, function ($job) use ($refresh_token) {
            return $refresh_token->character_id === $job->character_id;
        });
    }

    /** @test */
    public function it_dispatches_corporation_member_tracking_as_director()
    {
        $refresh_token = Event::fakeFor( function () {
            return factory(RefreshToken::class)->create([
                'scopes' => ['esi-corporations.track_members.v1']
            ]);
        });

        factory(CharacterAffiliation::class)->create([
            'character_id' => $refresh_token->character_id,
            'corporation_id' => factory(CorporationInfo::class)
        ]);

        factory(CharacterRole::class)->create([
            'character_id' => $refresh_token->character_id,
            'roles' => ['Director']
        ]);

        Bus::fake();

        (new UpdateCorporation())->handle();

        Bus::assertDispatched(CorporationMemberTrackingJob::class, function ($job) use ($refresh_token) {
            return $refresh_token->character_id === $job->character_id;
        });
    }
}
