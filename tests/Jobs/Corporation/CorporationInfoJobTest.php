<?php

use Illuminate\Support\Facades\Bus;
use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Corporation\CorporationInfoJob;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;

beforeEach(function () {
    $this->corporation_id = testCharacter()->corporation->corporation_id;
});

test('retrieve test', function () {
    $mockData = CorporationInfo::factory()->make([
        'date_founded' => now()->toDateString(),
    ]);

    Bus::fake();

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult((object) $mockData->toArray()));

    $job = new CorporationInfoJob($this->corporation_id);
    $job->executeJob($esi);

    expect(CorporationInfo::where('name', $mockData->name)->exists())->toBeTrue();
});

test('returns early if cached', function () {
    CorporationInfo::where('corporation_id', $this->corporation_id)->delete();

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $job = new CorporationInfoJob($this->corporation_id);
    $job->executeJob($esi);

    expect(CorporationInfo::where('corporation_id', $this->corporation_id)->count())->toBe(0);
});
