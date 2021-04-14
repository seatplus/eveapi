<?php

namespace Seatplus\Eveapi\Tests\Jobs\Corporation;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Esi\Jobs\Corporation\CorporationMemberTrackingAction;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Corporation\CorporationDivisionsJob;
use Seatplus\Eveapi\Jobs\Corporation\CorporationMemberTrackingJob;
use Seatplus\Eveapi\Models\Corporation\CorporationDivision;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

class CorporationDivisionsJobTest extends TestCase
{

    use MockRetrieveEsiDataAction;

    private JobContainer $job_container;

    public function setUp(): void
    {
        parent::setUp();

        $refresh_token = Event::fakeFor(function () {
            $this->test_character->refresh_token()->update(['scopes' => ['esi-corporations.read_divisions.v1']]);
            $this->test_character->roles()->update(['roles' => ['Director']]);

            return $this->test_character->refresh()->refresh_token;
        });

        $this->job_container = new JobContainer(['refresh_token' => $refresh_token]);
    }

    /** @test */
    public function itRunsTheJob() {

        $this->buildEsiResponseMockData();

        $this->assertCount(0, CorporationDivision::all());

        CorporationDivisionsJob::dispatchSync($this->job_container);

        $this->assertCount(14, CorporationDivision::all());

        $this->assertTrue(CorporationDivision::first()->corporation instanceof CorporationInfo);

    }

    private function buildEsiResponseMockData(): void
    {
        $mock_data = [
            "hangar" => [
                (object) ["division" => 1, "name" => "Loot and Salavage"],
                (object) ["division" => 2, "name" => "Directors"],
                (object) ["division" => 3, "name" => "Capital Farm Supplies"],
                (object) ["division" => 4, "name" => "Member Hangar"],
                (object) ["division" => 5, "name" => "Member Ships"],
                (object) ["division" => 6, "name" => "Rolling Ships"],
                (object) ["division" => 7, "name" => "Common ships and modules"],
            ],
            "wallet" => [
                (object) ["division" => 1, "name" => "Wallet 1"],
                (object) ["division" => 2, "name" => "Wallet 2"],
                (object) ["division" => 3, "name" => "Wallet 3"],
                (object) ["division" => 4, "name" => "Wallet 4"],
                (object) ["division" => 5, "name" => "Wallet 5"],
                (object) ["division" => 6, "name" => "Wallet 6"],
                (object) ["division" => 7, "name" => "Wallet 7"],
            ]
        ];

        $this->mockRetrieveEsiDataAction($mock_data);
    }


}