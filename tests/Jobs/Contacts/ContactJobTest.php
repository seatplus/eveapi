<?php


use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Contacts\AllianceContactJob;
use Seatplus\Eveapi\Jobs\Contacts\AllianceContactLabelJob;
use Seatplus\Eveapi\Jobs\Contacts\CharacterContactJob;
use Seatplus\Eveapi\Jobs\Contacts\CharacterContactLabelJob;
use Seatplus\Eveapi\Jobs\Contacts\CorporationContactJob;
use Seatplus\Eveapi\Jobs\Contacts\CorporationContactLabelJob;
use Seatplus\Eveapi\Tests\TestCase;



test('alliance contact job', function () {
    Queue::fake();

    // Assert that no jobs were pushed...
    Queue::assertNothingPushed();

    AllianceContactJob::dispatch()->onQueue('default');

    // Assert a job was pushed to a given queue...
    Queue::assertPushedOn('default', AllianceContactJob::class);
});

test('alliance contact label job', function () {
    Queue::fake();

    // Assert that no jobs were pushed...
    Queue::assertNothingPushed();

    AllianceContactLabelJob::dispatch()->onQueue('default');

    // Assert a job was pushed to a given queue...
    Queue::assertPushedOn('default', AllianceContactLabelJob::class);
});

test('corporation contact job', function () {
    Queue::fake();

    // Assert that no jobs were pushed...
    Queue::assertNothingPushed();

    CorporationContactJob::dispatch()->onQueue('default');

    // Assert a job was pushed to a given queue...
    Queue::assertPushedOn('default', CorporationContactJob::class);
});

test('corporation contact label job', function () {
    Queue::fake();

    // Assert that no jobs were pushed...
    Queue::assertNothingPushed();

    CorporationContactLabelJob::dispatch()->onQueue('default');

    // Assert a job was pushed to a given queue...
    Queue::assertPushedOn('default', CorporationContactLabelJob::class);
});

test('character contact job', function () {
    Queue::fake();

    // Assert that no jobs were pushed...
    Queue::assertNothingPushed();

    CharacterContactJob::dispatch()->onQueue('default');

    // Assert a job was pushed to a given queue...
    Queue::assertPushedOn('default', CharacterContactJob::class);
});

test('character contact label job', function () {
    Queue::fake();

    // Assert that no jobs were pushed...
    Queue::assertNothingPushed();

    CharacterContactLabelJob::dispatch()->onQueue('default');

    // Assert a job was pushed to a given queue...
    Queue::assertPushedOn('default', CharacterContactLabelJob::class);
});
