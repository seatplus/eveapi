<?php


namespace Seatplus\Eveapi\Tests\Unit\Models;


use Faker\Factory;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Character\CharacterAffiliationJob;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Applications;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Tests\TestCase;

class CharacterInfoTest extends TestCase
{
    /** @test */
    public function characterHasRefreshTokenRelationTest()
    {

        $this->assertInstanceOf(RefreshToken::class, $this->test_character->refresh_token);
    }

    /** @test */
    public function characterHasAllianceRelationTest()
    {
        $faker = Factory::create();

        $alliance_id = $faker->numberBetween(99000000,100000000);

        $affiliation = factory(CharacterAffiliation::class)->create([
            'alliance_id' => $alliance_id
        ]);

        $character = $affiliation->character()->save(factory(CharacterInfo::class)->create());

        $affiliation->alliance()->associate(factory(AllianceInfo::class)->create([
            'alliance_id' => $alliance_id
        ]));

        $this->assertInstanceOf(AllianceInfo::class, $character->alliance);
    }

    /** @test */
    public function characterHasCorporationRelationTest()
    {

        $this->assertInstanceOf(CorporationInfo::class, $this->test_character->corporation);
    }

    /** @test */
    public function it_has_application_relationship()
    {
        $application = factory(Applications::class)->create([
            'corporation_id' => $this->test_character->corporation->corporation_id,
            'applicationable_type' => CharacterInfo::class,
            'applicationable_id' => $this->test_character->character_id
        ]);

        $this->assertInstanceOf(Applications::class, $this->test_character->application);
    }

    /** @test */
    public function it_has_asset_relationship()
    {
        $asset = Asset::factory()->create([
            'assetable_id' => $this->test_character->character_id,
            'assetable_type' => CharacterInfo::class
        ]);

        $this->assertInstanceOf(Asset::class, $this->test_character->refresh()->assets->first());
    }

    /** @test */
    public function upon_creation_dispatch_affiliation_job()
    {
        Queue::fake();

        $character = factory(CharacterInfo::class)->create();

        Queue::assertPushedOn('high', CharacterAffiliationJob::class);
    }

}
