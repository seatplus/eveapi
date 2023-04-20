<?php

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Contracts\CharacterContractItemsJob;
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

    $this->assertRetrieveEsiDataIsNotCalled();

    CharacterContractItemsJob::dispatch(testCharacter()->character_id, $mock_data->first()->contract_id);

    // Assert no
    Queue::assertNotPushed(ResolveUniverseTypeByIdJob::class);
});

it('dispatches resolve universe type job if type is unknown', function () {
    Queue::fake();

    $mock_data = ContractItem::factory()->withoutType()->count(5)->make();

    $contract = \Illuminate\Support\Facades\Event::fakeFor(fn () => Contract::factory()->create([
        'contract_id' => $mock_data->first()->contract_id,
    ]));

    mockRetrieveEsiDataAction($mock_data->toArray());

    $job = new CharacterContractItemsJob(testCharacter()->character_id, $contract->contract_id);

    $refresh_token = updateRefreshTokenScopes($this->test_character->refresh_token, ['esi-contracts.read_character_contracts.v1']);
    $refresh_token->save();

    $job->handle();

    expect(ContractItem::all())->toHaveCount(5);

    Queue::assertPushed(ResolveUniverseTypeByIdJob::class);
});
