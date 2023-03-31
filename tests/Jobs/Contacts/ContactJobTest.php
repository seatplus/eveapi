<?php


use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Contacts\AllianceContactJob;
use Seatplus\Eveapi\Jobs\Contacts\AllianceContactLabelJob;
use Seatplus\Eveapi\Jobs\Contacts\CharacterContactJob;
use Seatplus\Eveapi\Jobs\Contacts\CharacterContactLabelJob;
use Seatplus\Eveapi\Jobs\Contacts\CorporationContactJob;
use Seatplus\Eveapi\Jobs\Contacts\CorporationContactLabelJob;

test('alliance contact job', function () {
    Queue::fake();

    // Assert that no jobs were pushed...
    Queue::assertNothingPushed();

    AllianceContactJob::dispatch(testCharacter()->corporation->alliance->alliance_id)->onQueue('default');

    // Assert a job was pushed to a given queue...
    Queue::assertPushedOn('default', AllianceContactJob::class);
});

test('alliance contact label job', function () {
    Queue::fake();

    // Assert that no jobs were pushed...
    Queue::assertNothingPushed();

    AllianceContactLabelJob::dispatch(testCharacter()->corporation->alliance->alliance_id)->onQueue('default');

    // Assert a job was pushed to a given queue...
    Queue::assertPushedOn('default', AllianceContactLabelJob::class);
});

test('corporation contact job', function () {
    Queue::fake();

    // Assert that no jobs were pushed...
    Queue::assertNothingPushed();

    CorporationContactJob::dispatch(testCharacter()->corporation->corporation_id)->onQueue('default');

    // Assert a job was pushed to a given queue...
    Queue::assertPushedOn('default', CorporationContactJob::class);
});

test('corporation contact label job', function () {
    Queue::fake();

    // Assert that no jobs were pushed...
    Queue::assertNothingPushed();

    CorporationContactLabelJob::dispatch(testCharacter()->corporation->corporation_id)->onQueue('default');

    // Assert a job was pushed to a given queue...
    Queue::assertPushedOn('default', CorporationContactLabelJob::class);
});

test('character contact job', function () {
    Queue::fake();

    // Assert that no jobs were pushed...
    Queue::assertNothingPushed();

    CharacterContactJob::dispatch(testCharacter()->character_id)->onQueue('default');

    // Assert a job was pushed to a given queue...
    Queue::assertPushedOn('default', CharacterContactJob::class);
});

test('character contact label job', function () {
    Queue::fake();

    // Assert that no jobs were pushed...
    Queue::assertNothingPushed();

    CharacterContactLabelJob::dispatch(testCharacter()->character_id)->onQueue('default');

    // Assert a job was pushed to a given queue...
    Queue::assertPushedOn('default', CharacterContactLabelJob::class);
});

test('ContactJob using ContactBaseJob and finds refresh_token', function (string $flavour) {
    $required_scopes = [
        'esi-characters.read_contacts.v1',
        'esi-corporations.read_contacts.v1',
        'esi-alliances.read_contacts.v1',
    ];

    updateRefreshTokenScopes($this->test_character->refresh_token, $required_scopes)->save();

    $job = match ($flavour) {
        'character' => new CharacterContactJob($this->test_character->character_id),
        'corporation' => new CorporationContactJob($this->test_character->corporation->corporation_id),
        'alliance' => new AllianceContactJob($this->test_character->corporation->alliance->alliance_id),
        'character_label' => new CharacterContactLabelJob($this->test_character->character_id),
        'corporation_label' => new CorporationContactLabelJob($this->test_character->corporation->corporation_id),
        'alliance_label' => new AllianceContactLabelJob($this->test_character->corporation->alliance->alliance_id),
    };

    expect($job->getRefreshToken())->character_id->toBe($this->test_character->character_id);
})->with([
    'character',
    'corporation',
    'alliance',
    'character_label',
    'corporation_label',
    'alliance_label',
]);
