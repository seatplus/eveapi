<?php

namespace Seatplus\Eveapi\Tests\Unit\Models;

use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Wallet\Balance;
use Seatplus\Eveapi\Tests\TestCase;

class BalanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function itHasCorporationReleationship()
    {
        $balance = Event::fakeFor(fn () => Balance::factory()->withDivision()->create([
            'balanceable_id' => $this->test_character->corporation->corporation_id,
            'balanceable_type' => CorporationInfo::class,
        ]));

        $this->assertNotNull($balance->refresh()->balanceable);
    }
}