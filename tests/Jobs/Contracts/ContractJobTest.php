<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Contracts\CharacterContractsJob;
use Seatplus\Eveapi\Models\Contracts\Contract;

beforeEach(function () {
    Queue::fake();

    $refresh_token = updateRefreshTokenScopes($this->test_character->refresh_token, ['esi-contracts.read_character_contracts.v1']);
    $refresh_token->save();
});

test('job is being dispatched', function () {
    Queue::fake();

    Queue::assertNothingPushed();

    CharacterContractsJob::dispatch(testCharacter()->character_id)->onQueue('default');

    Queue::assertPushedOn('default', CharacterContractsJob::class);
});

it('runs with empty response', function () {
    mockEsiClient(
        'contracts->getCharactersCharacterIdContracts',
        makeEsiResult([])
    );

    runJob(new CharacterContractsJob(testCharacter()->character_id));
});

it('creates contract job', function () {
    $mock_data = buildContractJobMockEsiData();

    expect($this->test_character->refresh()->contracts)->toHaveCount(0);

    Event::fakeFor(fn () => runJob(new CharacterContractsJob(testCharacter()->character_id)));

    expect(Contract::all())->toHaveCount(5);
    expect($this->test_character->refresh()->contracts)->toHaveCount(5);
});

it('creates contract job other way', function () {
    buildContractJobMockEsiData();

    Event::fakeFor(fn () => runJob(new CharacterContractsJob(testCharacter()->character_id)));

    expect(Contract::all())->toHaveCount(5);
});

// Helpers
function buildContractJobMockEsiData(int $count = 5)
{
    $mock_data = Contract::factory()->count($count)->make();

    mockEsiClient(
        'contracts->getCharactersCharacterIdContracts',
        makeEsiResult(array_map(fn ($c) => (object) $c, $mock_data->toArray()))
    );

    return $mock_data;
}
