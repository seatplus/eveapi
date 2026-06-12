<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Queue;
use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiClient\Exceptions\RequestFailedException;
use Seatplus\Eveapi\Jobs\Alliances\AllianceInfoJob;
use Seatplus\Eveapi\Jobs\Character\CharacterAffiliationJob;
use Seatplus\Eveapi\Jobs\Corporation\CorporationInfoJob;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;

it('handles follow-up job', function (string $jobClass, array $configuration = [], bool $pushed = true) {
    Queue::fake();
    Queue::assertNothingPushed();

    $characterId = CharacterAffiliation::factory()->make()->character_id;
    $characterId = Arr::get($configuration, 'character_id', $characterId);

    $character = CharacterInfo::factory()->create([
        'character_id' => $characterId,
    ]);
    $character->characterAffiliation()->delete();

    $attributes = array_merge([
        'character_id' => $characterId,
    ], Arr::only($configuration, ['alliance_id', 'corporation_id']));

    $characterAffiliation = CharacterAffiliation::factory()->create([
        'character_id' => $character->character_id,
        ...$attributes,
    ]);

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([(object) $characterAffiliation->toArray()]));

    if ($jobClass === CorporationInfoJob::class && $pushed) {
        $characterAffiliation->corporation()->delete();
    }

    $job = new CharacterAffiliationJob($characterId);
    $job->executeJob($esi);

    if ($pushed) {
        Queue::assertPushedOn('high', $jobClass);
    } else {
        Queue::assertNotPushed($jobClass);
    }
})->with([
    'dispatching alliance job, if alliance is unknown' => [AllianceInfoJob::class, ['alliance_id' => 123456]],
    'not dispatching alliance job, if no alliance ' => [AllianceInfoJob::class, ['alliance_id' => null], false],
    'not dispatching alliance job, if alliance is known ' => fn () => [
        AllianceInfoJob::class,
        ['alliance_id' => AllianceInfo::factory()->create()->alliance_id],
        false,
    ],
    'dispatching corporation job, if corporation is unknown' => [CorporationInfoJob::class],
    'not dispatching corporation job, if corporation is known' => fn () => [
        CorporationInfoJob::class,
        ['corporation_id' => CorporationInfo::factory()->create()->corporation_id],
        false,
    ],
]);

it('applies binary search and caches it if one id is invalid', function () {
    Queue::fake();

    CharacterAffiliation::query()->delete();
    $mockData = CharacterAffiliation::factory()->make();

    $ids = [123456789, $mockData->character_id];

    $exceptionMock = Mockery::mock(Exception::class);
    $exceptionMock->shouldReceive('getResponse->getReasonPhrase')->andReturn('Invalid character ID');
    $exception = new RequestFailedException($exceptionMock, new EsiResponse(json_encode([]), [], 'now', 200));

    $esi = Mockery::mock(EsiClient::class);
    $esi->shouldReceive('withToken')->andReturnSelf();
    $esi->shouldReceive('assertScope')->andReturnNull();
    $esi->shouldReceive('invoke')
        ->once()->ordered()->andThrow($exception);
    $esi->shouldReceive('invoke')
        ->once()->ordered()->andThrow($exception);
    $esi->shouldReceive('invoke')
        ->once()->ordered()->andReturn(makeEsiRawResponse(makeEsiResult([(object) $mockData->toArray()])));

    $job = new CharacterAffiliationJob($ids);
    $job->executeJob($esi);

    expect(cache('invalid_character_ids'))->toBe([123456789])
        ->and(CharacterAffiliation::all())->toHaveCount(1)
        ->and(CharacterAffiliation::first())->character_id
        ->toBe($mockData->character_id);
});

it('skips db write when character affiliation response is a cached load', function () {
    CharacterAffiliation::query()->delete();
    $mockData = CharacterAffiliation::factory()->make();

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([(object) $mockData->toArray()], isCachedLoad: true));

    $job = new CharacterAffiliationJob($mockData->character_id);
    $job->executeJob($esi);

    expect(CharacterAffiliation::count())->toBe(0);
});
