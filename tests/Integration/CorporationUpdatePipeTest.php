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
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Character\CharacterRole;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Tests\TestCase;

class CorporationUpdatePipeTest extends TestCase
{
    /** @test */
    public function it_dispatches_corporation_member_tracking_as_non_director()
    {
        $this->test_character->refresh_token()->update(['scopes' => ['esi-corporations.track_members.v1']]);
        $this->test_character->roles()->update(['roles' => []]);

        Bus::fake();

        (new UpdateCorporation)->handle();

        Bus::assertNotDispatched(CorporationMemberTrackingJob::class, function ($job){
            return $this->test_character->character_id === $job->character_id;
        });
    }

    /** @test */
    public function it_dispatches_corporation_member_tracking_as_director()
    {

        $this->test_character->refresh_token()->update(['scopes' => ['esi-corporations.track_members.v1']]);
        $this->test_character->roles()->update(['roles' => ['Director']]);

        Bus::fake();

        (new UpdateCorporation)->handle();

        Bus::assertDispatched(CorporationMemberTrackingJob::class, function ($job) {
            return $this->test_character->character_id === $job->character_id;
        });
    }
}
