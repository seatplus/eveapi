<?php


use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Contracts\CharacterContractsJob;
use Seatplus\Eveapi\Models\Contracts\Contract;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

uses(MockRetrieveEsiDataAction::class);

beforeEach(function () {
    Queue::fake();

    $refresh_token = updateRefreshTokenScopes($this->test_character->refresh_token, ['esi-contracts.read_character_contracts.v1']);
    $refresh_token->save();
});

test('job is being dispatched', function () {
    Queue::fake();

    // Assert that no jobs were pushed...
    Queue::assertNothingPushed();

    CharacterContractsJob::dispatch(testCharacter()->character_id)->onQueue('default');

    // Assert a job was pushed to a given queue...
    Queue::assertPushedOn('default', CharacterContractsJob::class);
});

it('runs with empty response', function () {
    mockRetrieveEsiDataAction([]);

    $job = new CharacterContractsJob(testCharacter()->character_id);

    $job->handle();
});

it('creates contract job', function () {
    $mock_data = buildContractJobMockEsiData();

    $job = new CharacterContractsJob(testCharacter()->character_id);

    expect($this->test_character->refresh()->contracts)->toHaveCount(0);

    Event::fakeFor(fn () => $job->handle());

    expect(Contract::all())->toHaveCount(5);
    expect($this->test_character->refresh()->contracts)->toHaveCount(5);
});

it('creates contract job other way', function () {
    $mock_data = buildContractJobMockEsiData();


    Event::fakeFor(fn () => (new CharacterContractsJob(testCharacter()->character_id))->handle());

    expect(Contract::all())->toHaveCount(5);
});

// Helpers
function buildContractJobMockEsiData(int $count = 5)
{
    $mock_data = Contract::factory()->count($count)->make();

    mockRetrieveEsiDataAction($mock_data->toArray());

    return $mock_data;
}
