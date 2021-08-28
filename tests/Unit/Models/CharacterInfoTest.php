<?php


namespace Seatplus\Eveapi\Tests\Unit\Models;

use Faker\Factory;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Character\CharacterAffiliationJob;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Application;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Contracts\Contract;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Wallet\Balance;
use Seatplus\Eveapi\Tests\TestCase;

class CharacterInfoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
    }

    /** @test */
    public function characterHasRefreshTokenRelationTest()
    {
        $this->assertInstanceOf(RefreshToken::class, $this->test_character->refresh_token);
    }

    /** @test */
    public function characterHasAllianceRelationTest()
    {
        $faker = Factory::create();

        $alliance_id = $faker->numberBetween(99000000, 100000000);

        $affiliation = CharacterAffiliation::factory()->create([
            'alliance_id' => $alliance_id,
        ]);

        $character = $affiliation->character()->save(CharacterInfo::factory()->create());

        $affiliation->alliance()->associate(AllianceInfo::factory()->create([
            'alliance_id' => $alliance_id,
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
        $application = Application::factory()->create([
            'corporation_id' => $this->test_character->corporation->corporation_id,
            'applicationable_type' => CharacterInfo::class,
            'applicationable_id' => $this->test_character->character_id,
        ]);

        $this->assertInstanceOf(Application::class, $this->test_character->application);
    }

    /** @test */
    public function it_has_asset_relationship()
    {
        $asset = Asset::factory()->create([
            'assetable_id' => $this->test_character->character_id,
            'assetable_type' => CharacterInfo::class,
        ]);

        $this->assertInstanceOf(Asset::class, $this->test_character->refresh()->assets->first());
    }

    /** @test */
    public function upon_creation_dispatch_affiliation_job()
    {
        Queue::fake();

        $character = CharacterInfo::factory()->create();

        Queue::assertPushedOn('high', CharacterAffiliationJob::class);
    }

    /** @test */
    public function it_has_contract_relationship()
    {
        $contract = Contract::factory()->create();
        $this->test_character->contracts()->attach($contract->contract_id);

        $this->assertInstanceOf(Contract::class, $this->test_character->refresh()->contracts->first());

        // Test reverse too
        $this->assertInstanceOf(CharacterInfo::class, $contract->characters->first());
    }

    /** @test */
    public function character_has_balance_relationship()
    {
        $balance = Balance::factory()->withDivision()->create([
            'balanceable_id' => $this->test_character->character_id,
            'balanceable_type' => CharacterInfo::class,
        ]);

        $this->assertInstanceOf(Balance::class, $this->test_character->refresh()->balance);
    }
}
