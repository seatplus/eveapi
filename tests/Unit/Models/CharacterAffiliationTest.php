<?php


namespace Seatplus\Eveapi\Tests\Unit\Models;

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Alliances\AllianceInfoJob;
use Seatplus\Eveapi\Jobs\Corporation\CorporationInfoJob;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Observers\CharacterAffiliationObserver;
use Seatplus\Eveapi\Tests\TestCase;

class CharacterAffiliationTest extends TestCase
{
    /** @test */
    public function upon_creation_dispatch_corp_job()
    {
        Queue::fake();

        // It needs an existing character on record else we don't bother
        $character = CharacterInfo::factory()->create();


        // remove corporation and alliance
        $character->character_affiliation->corporation->delete();
        if ($character->character_affiliation->alliance) {
            $character->character_affiliation->alliance->delete();
        }

        // It needs an existing character on record else we don't bother
        //factory(CharacterInfoJob::class)->create(['character_id' => $character_affiliation->character_id]);

        (new CharacterAffiliationObserver)->created($character->character_affiliation->refresh());

        Queue::assertPushedOn('high', CorporationInfoJob::class);
        if ($character->character_affiliation->alliance) {
            Queue::assertPushedOn('high', AllianceInfoJob::class);
        }
    }

    /** @test */
    public function upon_updating_dispatch_corp_job()
    {
        Queue::fake();

        // It needs an existing character on record else we don't bother
        $character = CharacterInfo::factory()->create();

        $character_affiliation = $character->character_affiliation;
        $character_affiliation->corporation_id = 1234;
        $character_affiliation->alliance_id = 5678;

        // It needs an existing character on record else we don't bother
        //factory(CharacterInfoJob::class)->create(['character_id' => $character_affiliation->character_id]);

        (new CharacterAffiliationObserver)->updating($character_affiliation);

        Queue::assertPushedOn('high', CorporationInfoJob::class);
        Queue::assertPushedOn('high', AllianceInfoJob::class);
    }
}
