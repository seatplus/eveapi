<?php

use Illuminate\Support\Facades\Bus;
use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Corporation\CorporationInfoJob;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;

beforeEach(function () {
    $this->corporation_id = testCharacter()->corporation->corporation_id;
});

test('retrieve test', function () {
    $mock_data = CorporationInfo::factory()->make([
        'date_founded' => now()->toDateString(),
    ]);

    Bus::fake();

    $dto = (object) array_merge(['isCachedLoad' => false], $mock_data->toArray());
    mockEsiClient('corporation->getCorporationsCorporationId', $dto);

    runJob(new CorporationInfoJob($this->corporation_id));

    $this->assertDatabaseHas('corporation_infos', [
        'name' => $mock_data->name,
    ]);
});

test('returns early if cached', function () {
    CorporationInfo::where('corporation_id', $this->corporation_id)->delete();

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $job = new CorporationInfoJob($this->corporation_id);
    $job->executeJob($esi);

    expect(CorporationInfo::where('corporation_id', $this->corporation_id)->count())->toBe(0);
});
