<?php


namespace Seatplus\Eveapi\Tests\Jobs\Contacts;


use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Contacts\AllianceContactJob;
use Seatplus\Eveapi\Jobs\Contacts\AllianceContactLabelJob;
use Seatplus\Eveapi\Jobs\Contacts\CharacterContactJob;
use Seatplus\Eveapi\Jobs\Contacts\CharacterContactLabelJob;
use Seatplus\Eveapi\Jobs\Contacts\CorporationContactJob;
use Seatplus\Eveapi\Jobs\Contacts\CorporationContactLabelJob;
use Seatplus\Eveapi\Tests\TestCase;

class ContactJobTest extends TestCase
{
    /** @test */
    public function AllianceContactJob()
    {

        Queue::fake();

        // Assert that no jobs were pushed...
        Queue::assertNothingPushed();

        AllianceContactJob::dispatch()->onQueue('default');

        // Assert a job was pushed to a given queue...
        Queue::assertPushedOn('default', AllianceContactJob::class);
    }

    /** @test */
    public function AllianceContactLabelJob()
    {
        Queue::fake();

        // Assert that no jobs were pushed...
        Queue::assertNothingPushed();

        AllianceContactLabelJob::dispatch()->onQueue('default');

        // Assert a job was pushed to a given queue...
        Queue::assertPushedOn('default', AllianceContactLabelJob::class);
    }

    /** @test */
    public function CorporationContactJob()
    {
        Queue::fake();

        // Assert that no jobs were pushed...
        Queue::assertNothingPushed();

        CorporationContactJob::dispatch()->onQueue('default');

        // Assert a job was pushed to a given queue...
        Queue::assertPushedOn('default', CorporationContactJob::class);
    }

    /** @test */
    public function CorporationContactLabelJob()
    {
        Queue::fake();

        // Assert that no jobs were pushed...
        Queue::assertNothingPushed();

        CorporationContactLabelJob::dispatch()->onQueue('default');

        // Assert a job was pushed to a given queue...
        Queue::assertPushedOn('default', CorporationContactLabelJob::class);
    }

    /** @test */
    public function CharacterContactJob()
    {
        Queue::fake();

        // Assert that no jobs were pushed...
        Queue::assertNothingPushed();

        CharacterContactJob::dispatch()->onQueue('default');

        // Assert a job was pushed to a given queue...
        Queue::assertPushedOn('default', CharacterContactJob::class);
    }

    /** @test */
    public function CharacterContactLabelJob()
    {
        Queue::fake();

        // Assert that no jobs were pushed...
        Queue::assertNothingPushed();

        CharacterContactLabelJob::dispatch()->onQueue('default');

        // Assert a job was pushed to a given queue...
        Queue::assertPushedOn('default', CharacterContactLabelJob::class);
    }
}
