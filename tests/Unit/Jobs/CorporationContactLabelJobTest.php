<?php

use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\Eveapi\Jobs\Contacts\CorporationContactLabelJob;
use Seatplus\Eveapi\Models\Contacts\Label;

it('returns early if cached', function () {

    $response = mock(EsiResponse::class);
    $response->shouldReceive('isCachedLoad')->once()->andReturn(true);

    $job = mock(CorporationContactLabelJob::class)->makePartial();
    $job->shouldReceive('retrieve')->once()->andReturn($response);
    $job->corporation_id = 123;

    $job->executeJob();

    expect(true)->toBeTrue();
});

it('increments page', function () {

    Queue::fake();

    $contact_label = Label::factory()->count(2)->make();

    $response1 = new EsiResponse(json_encode($contact_label->toArray()), ['X-Pages' => 2], 'now', 200);
    $response2 = new EsiResponse('{}', ['X-Pages' => 2], 'now', 200);

    $job = mock(CorporationContactLabelJob::class)->makePartial();
    $job->__construct(123, 456);
    $job->shouldReceive('retrieve')->twice()->andReturns($response1, $response2);

    $job->executeJob();

    expect($job->getPage())->toEqual(2);
});
