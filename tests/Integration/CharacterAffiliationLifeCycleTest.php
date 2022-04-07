<?php

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Alliances\AllianceInfoJob;
use Seatplus\Eveapi\Jobs\Character\CharacterAffiliationJob;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;
use Seatplus\Eveapi\Models\Character\CharacterInfo;

it('dispatches alliance job', function () {
    Queue::fake();
    Queue::assertNothingPushed();

    $character = CharacterInfo::factory()->create();
    $character->character_affiliation()->delete();

    $character_affiliation = CharacterAffiliation::factory()->create([
        'character_id' => $character->character_id,
        'alliance_id' => 123456,
    ]);

    Queue::assertPushedOn('high', AllianceInfoJob::class);
});

it('dispatches no alliance job if alliance id is null', function () {
    Queue::fake();
    Queue::assertNothingPushed();

    $character = CharacterInfo::factory()->create();
    $character->character_affiliation()->delete();

    $character_affiliation = CharacterAffiliation::factory()->create([
        'character_id' => $character->character_id,
        'alliance_id' => null,
    ]);

    Queue::assertNotPushed(AllianceInfoJob::class);
});


it('does not update affiliation younger then an hours', function () {

    // expect the test_character entry to exist
    expect(CharacterAffiliation::all())->toHaveCount(1);

    // delete the entry
    CharacterAffiliation::query()->delete();

    $old_data = CharacterAffiliation::factory()->create([
        'last_pulled' => now()->subMinutes(42),
    ]);

    expect(CharacterAffiliation::all())->toHaveCount(1);

    $this->assertDatabaseHas('character_affiliations', [
        'last_pulled' => $old_data->last_pulled,
    ]);

    noRetrieveEsiDataAction();

    (new CharacterAffiliationJob())->handle();

    $this->assertDatabaseHas('character_affiliations', [
        'last_pulled' => $old_data->last_pulled,
        'character_id' => $old_data->character_id,
    ]);

});

it('updates affiliation older then an hours', function () {

    // expect the test_character entry to exist
    expect(CharacterAffiliation::all())->toHaveCount(1);

    // delete the entry
    CharacterAffiliation::query()->delete();

    expect(CharacterAffiliation::all())->toHaveCount(0);

    $old_data = CharacterAffiliation::factory()->create([
        'last_pulled' => now()->subMinutes(61),
    ]);

    mockRetrieveEsiDataAction([
        $old_data->toArray(),
    ]);

    $this->assertDatabaseHas('character_affiliations', [
        'last_pulled' => $old_data->last_pulled,
    ]);

    Redis::flushall();
    //$return_value = (new CharacterAffiliationAction)->execute();
    (new CharacterAffiliationJob)->handle();

    //$this->assertNull($return_value);

    expect(CharacterAffiliation::first())
        ->last_pulled->not()->toBe($old_data->last_pulled);

    $this->assertDatabaseMissing('character_affiliations', [
        'last_pulled' => $old_data->last_pulled,
    ]);
});

it('updates affiliation by id', function () {

    // expect the test_character entry to exist
    expect(CharacterAffiliation::all())->toHaveCount(1);

    // delete the entry
    CharacterAffiliation::query()->delete();

    expect(CharacterAffiliation::all())->toHaveCount(0);

    $old_data = CharacterAffiliation::factory()->create();

    mockRetrieveEsiDataAction([$old_data->toArray()]);

    $this->assertDatabaseHas('character_affiliations', [
        'last_pulled' => $old_data->last_pulled,
    ]);

    Redis::flushall();

    (new CharacterAffiliationJob($old_data->character_id))->handle();

    expect(CharacterAffiliation::first())
        ->last_pulled->not()->toBe($old_data->last_pulled);

    $this->assertDatabaseMissing('character_affiliations', [
        'last_pulled' => $old_data->last_pulled,
    ]);
});

it('updates cached ids', function () {

    // expect the test_character entry to exist
    expect(CharacterAffiliation::all())->toHaveCount(1);

    // delete the entry
    CharacterAffiliation::query()->delete();

    expect(CharacterAffiliation::all())->toHaveCount(0);

    $character_affiliation = CharacterAffiliation::factory()->make();

    mockRetrieveEsiDataAction([$character_affiliation->toArray()]);

    $this->assertDatabaseMissing('character_affiliations', [
        'character_id' => $character_affiliation->character_id,
    ]);

    Redis::flushall();

    \Seatplus\Eveapi\Services\Jobs\CharacterAffiliationService::make()
        ->queue($character_affiliation->character_id);

    (new CharacterAffiliationJob)->handle();

    expect(CharacterAffiliation::all())->toHaveCount(1);

    expect(CharacterAffiliation::first())->character_id
        ->toBe($character_affiliation->character_id);
});
