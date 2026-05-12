<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Queue;
use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\EsiClient\Exceptions\RequestFailedException;
use Seatplus\Eveapi\Jobs\Alliances\AllianceInfoJob;
use Seatplus\Eveapi\Jobs\Character\CharacterAffiliationJob;
use Seatplus\Eveapi\Jobs\Corporation\CorporationInfoJob;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Services\Facade\RetrieveEsiData;

it('handles follow-up job', function (string $job_class, array $configuration = [], bool $pushed = true) {
    Queue::fake();
    Queue::assertNothingPushed();

    // get character_id from config or create a new one
    $character_id = CharacterAffiliation::factory()->make()->character_id;
    $character_id = Arr::get($configuration, 'character_id', $character_id);

    // If config contains has_character, we create a character and use its id
    $character = CharacterInfo::factory()->create([
        'character_id' => $character_id,
    ]);
    $character->character_affiliation()->delete();

    $attributes = array_merge([
        'character_id' => $character_id,
    ], Arr::only($configuration, ['alliance_id', 'corporation_id']));

    $character_affiliation = CharacterAffiliation::factory()->create([
        'character_id' => $character->character_id,
        ...$attributes,
    ]);

    mockRetrieveEsiDataAction([$character_affiliation->toArray()]);

    if ($job_class === CorporationInfoJob::class && $pushed) {
        $character_affiliation->corporation()->delete();
    }

    // run the job
    (new CharacterAffiliationJob($character_id))->handle();

    if ($pushed) {
        Queue::assertPushedOn('high', $job_class);
    } else {
        Queue::assertNotPushed($job_class);
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

it('applies binary search and chaches it if one id is invalid', function () {
    Queue::fake();

    CharacterAffiliation::query()->delete();
    $mock_data = CharacterAffiliation::factory()->make();

    // prepare the ids with a length of 2, the first id must be the invalid one
    $ids = [123456789, $mock_data->character_id];

    // Prepare the mock responses
    $exception_mock = Mockery::mock(Exception::class);
    $exception_mock->shouldReceive('getResponse->getReasonPhrase')->andReturn('Invalid character ID');
    // first create the exception
    $exception = new RequestFailedException($exception_mock, new EsiResponse(json_encode([]), [], 'now', 200));

    $mock_data = CharacterAffiliation::factory()->make();
    $response = new EsiResponse(json_encode([$mock_data]), [], 'now', 200);

    // Expectation for the 1st call
    RetrieveEsiData::shouldReceive('execute')
        ->once()
        ->andThrow($exception)

        // Expectation for the 2nd call
        ->shouldReceive('execute')
        ->once()
        ->andThrow($exception)

        // Expectation for the 3rd call
        ->shouldReceive('execute')
        ->once()
        ->andReturn($response);

    (new CharacterAffiliationJob($ids))->handle();

    // Check that first id is cached as invalid
    expect(cache('invalid_character_ids'))->toBe([123456789])
        ->and(CharacterAffiliation::all())->toHaveCount(1)
        ->and(CharacterAffiliation::first())->character_id
        ->toBe($mock_data->character_id);
});
