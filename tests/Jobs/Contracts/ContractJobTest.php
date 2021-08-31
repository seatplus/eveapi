<?php


use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Contracts\CharacterContractsJob;
use Seatplus\Eveapi\Models\Contracts\Contract;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

uses(TestCase::class);
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
    $this->mockRetrieveEsiDataAction([]);

    $job_container = new JobContainer([
        'refresh_token' => $this->test_character->refresh_token,
    ]);

    $job = new CharacterContractsJob($job_container);

    dispatch_now($job);
});

it('creates contract job', function () {
    $mock_data = buildMockEsiData();

    $job_container = new JobContainer([
        'refresh_token' => $this->test_character->refresh_token,
    ]);

    $job = new CharacterContractsJob($job_container);

    $this->assertCount(0, $this->test_character->refresh()->contracts);

    Event::fakeFor(fn () => dispatch_now($job));

    $this->assertCount(5, Contract::all());
    $this->assertCount(5, $this->test_character->refresh()->contracts);
});

it('creates contract job other way', function () {
    $mock_data = buildMockEsiData();

    $job_container = new JobContainer([
        'refresh_token' => $this->test_character->refresh_token,
    ]);

    Event::fakeFor(fn () => CharacterContractsJob::dispatchNow($job_container));

    $this->assertCount(5, Contract::all());
});

// Helpers
function buildMockEsiData(int $count = 5)
{
    $mock_data = Contract::factory()->count($count)->make();

    $this->mockRetrieveEsiDataAction($mock_data->toArray());

    return $mock_data;
}
