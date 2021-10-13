<?php


use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Contracts\CharacterContractsJob;
use Seatplus\Eveapi\Models\Contracts\Contract;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

uses(MockRetrieveEsiDataAction::class);

test('job is being dispatched', function () {
    Queue::fake();

    // Assert that no jobs were pushed...
    Queue::assertNothingPushed();

    $job_container = new JobContainer([
        'refresh_token' => $this->test_character->refresh_token,
    ]);

    CharacterContractsJob::dispatch($job_container)->onQueue('default');

    // Assert a job was pushed to a given queue...
    Queue::assertPushedOn('default', CharacterContractsJob::class);
});

it('runs with empty response', function () {
    mockRetrieveEsiDataAction([]);

    $job_container = new JobContainer([
        'refresh_token' => $this->test_character->refresh_token,
    ]);

    $job = new CharacterContractsJob($job_container);

    dispatch_now($job);
});

it('creates contract job', function () {
    $mock_data = buildContractJobMockEsiData();

    $job_container = new JobContainer([
        'refresh_token' => $this->test_character->refresh_token,
    ]);

    $job = new CharacterContractsJob($job_container);

    expect($this->test_character->refresh()->contracts)->toHaveCount(0);

    Event::fakeFor(fn () => dispatch_now($job));

    expect(Contract::all())->toHaveCount(5);
    expect($this->test_character->refresh()->contracts)->toHaveCount(5);
});

it('creates contract job other way', function () {
    $mock_data = buildContractJobMockEsiData();

    $job_container = new JobContainer([
        'refresh_token' => $this->test_character->refresh_token,
    ]);

    Event::fakeFor(fn () => CharacterContractsJob::dispatchNow($job_container));

    expect(Contract::all())->toHaveCount(5);
});

// Helpers
function buildContractJobMockEsiData(int $count = 5)
{
    $mock_data = Contract::factory()->count($count)->make();

    mockRetrieveEsiDataAction($mock_data->toArray());

    return $mock_data;
}
