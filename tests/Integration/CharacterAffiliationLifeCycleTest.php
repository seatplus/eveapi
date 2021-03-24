<?php


namespace Seatplus\Eveapi\Tests\Integration;


use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Alliances\AllianceInfoJob;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Tests\TestCase;

class CharacterAffiliationLifeCycleTest extends TestCase
{
    /** @test */
    public function it_dispatches_alliance_job()
    {

        Queue::assertNothingPushed();

        $character = CharacterInfo::factory()->create();
        $character->character_affiliation()->delete();

        $character_affiliation = CharacterAffiliation::factory()->create([
            'character_id' => $character->character_id,
            'alliance_id' => 123456
        ]);

        Queue::assertPushedOn('high', AllianceInfoJob::class);
    }

    /** @test */
    public function it_dispatches_no_alliance_job_if_alliance_id_is_null()
    {
        Queue::assertNothingPushed();

        $character = CharacterInfo::factory()->create();
        $character->character_affiliation()->delete();

        $character_affiliation = CharacterAffiliation::factory()->create([
            'character_id' => $character->character_id,
            'alliance_id' => null
        ]);

        Queue::assertNotPushed(AllianceInfoJob::class);
    }

}
