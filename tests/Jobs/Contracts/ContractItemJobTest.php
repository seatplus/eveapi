<?php


use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Contracts\ContractItemsJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Models\Contracts\Contract;
use Seatplus\Eveapi\Models\Contracts\ContractItem;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

uses(MockRetrieveEsiDataAction::class);

test('job is being dispatched', function () {
    Queue::fake();

    // Assert that no jobs were pushed...
    Queue::assertNothingPushed();

    $mock_data = ContractItem::factory()->count(1)->make();

    $job_container = new JobContainer([
        'refresh_token' => $this->test_character->refresh_token,
    ]);

    $this->assertRetrieveEsiDataIsNotCalled();

    ContractItemsJob::dispatch($mock_data->first()->contract_id, $job_container, 'character');

    // Assert no
    Queue::assertNotPushed(ResolveUniverseTypeByIdJob::class);
});

it('dispatches resolve universe type job if type is unknown', function () {
    Queue::fake();

    $mock_data = ContractItem::factory()->withoutType()->count(5)->make();

    $contract = \Illuminate\Support\Facades\Event::fakeFor(fn() => Contract::factory()->create([
        'contract_id' => $mock_data->first()->contract_id
    ]));

    mockRetrieveEsiDataAction($mock_data->toArray());

    $job_container = new JobContainer([
        'refresh_token' => $this->test_character->refresh_token,
    ]);

    $job = new ContractItemsJob($contract->contract_id, $job_container);

    $job->handle();

    expect(ContractItem::all())->toHaveCount(5);

    Queue::assertPushed(ResolveUniverseTypeByIdJob::class);
});
