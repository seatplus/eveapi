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

test('it converts the isk tax rate percentage to a fraction and stores the enlisted faction', function () {
    Bus::fake();

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult((object) [
        'name' => 'Some Corporation',
        'ticker' => 'TICK',
        'member_count' => 42,
        'ceo_id' => 90000001,
        'creator_id' => 90000002,
        'home_station_id' => 60003760,
        'shares' => 1000,
        'tax_rates' => (object) ['isk' => 10.0, 'loyalty_point' => 5.6],
        'enlisted_faction_id' => 500001,
    ]));

    $job = new CorporationInfoJob($this->corporation_id);
    $job->executeJob($esi);

    expect(CorporationInfo::find($this->corporation_id))
        // ESI sends 10.0 meaning 10%; the column holds the historical 0.0-1.0 fraction.
        ->tax_rate->toEqual(0.1)
        ->faction_id->toEqual(500001);
});

test('it stores a corporation that has no ceo or creator', function () {
    Bus::fake();

    // ESI 2026-08-04 made ceo_id/creator_id optional — a closed or npc_owned corporation reports
    // neither. Both columns were NOT NULL, so this payload used to abort with SQLSTATE 23502.
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult((object) [
        'name' => 'Closed Corporation',
        'ticker' => 'GONE',
        'member_count' => 0,
        'tax_rates' => (object) ['isk' => 0.0, 'loyalty_point' => 0.0],
        'state' => 'closed',
        'type' => 'npc_owned',
    ]));

    $job = new CorporationInfoJob($this->corporation_id);
    $job->executeJob($esi);

    expect(CorporationInfo::find($this->corporation_id))
        ->ceo_id->toBeNull()
        ->creator_id->toBeNull()
        ->name->toBe('Closed Corporation');
});
