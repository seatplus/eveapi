<?php

use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Corporation\CorporationDivisionsJob;
use Seatplus\Eveapi\Models\Corporation\CorporationDivision;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;


uses(MockRetrieveEsiDataAction::class);

beforeEach(function () {
    $refresh_token = Event::fakeFor(function () {
        updateRefreshTokenScopes($this->test_character->refresh_token, ['esi-corporations.read_divisions.v1'])->save();
        $this->test_character->roles()->update(['roles' => ['Director']]);

        return $this->test_character->refresh()->refresh_token;
    });

    $this->job_container = new JobContainer(['refresh_token' => $refresh_token]);
});

it('runs the job', function () {
    buildCorporationDivisionEsiResponseMockData();

    expect(CorporationDivision::all())->toHaveCount(0);

    CorporationDivisionsJob::dispatchSync($this->job_container);

    expect(CorporationDivision::all())->toHaveCount(14);

    expect(CorporationDivision::first()->corporation instanceof CorporationInfo)->toBeTrue();
});

// Helpers
function buildCorporationDivisionEsiResponseMockData(): void
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
        ],
    ];

    mockRetrieveEsiDataAction($mock_data);
}
