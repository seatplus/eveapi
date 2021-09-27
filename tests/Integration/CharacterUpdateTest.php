<?php


use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Testing\Fakes\PendingBatchFake;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetJob;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetsNameDispatchJob;
use Seatplus\Eveapi\Jobs\Character\CharacterInfoJob as CharacterInfoJob;
use Seatplus\Eveapi\Jobs\Character\CharacterRoleJob;
use Seatplus\Eveapi\Jobs\Character\CorporationHistoryJob;
use Seatplus\Eveapi\Jobs\Contacts\AllianceContactJob;
use Seatplus\Eveapi\Jobs\Contacts\AllianceContactLabelJob;
use Seatplus\Eveapi\Jobs\Contacts\CharacterContactJob;
use Seatplus\Eveapi\Jobs\Contacts\CharacterContactLabelJob;
use Seatplus\Eveapi\Jobs\Contacts\CorporationContactJob;
use Seatplus\Eveapi\Jobs\Contacts\CorporationContactLabelJob;
use Seatplus\Eveapi\Jobs\Contracts\CharacterContractsJob;
use Seatplus\Eveapi\Jobs\Hydrate\Character\CharacterAssetsHydrateBatch;
use Seatplus\Eveapi\Jobs\Hydrate\Character\CharacterRolesHydrateBatch;
use Seatplus\Eveapi\Jobs\Hydrate\Character\ContactHydrateBatch;
use Seatplus\Eveapi\Jobs\Hydrate\Character\ContractHydrateBatch;
use Seatplus\Eveapi\Jobs\Hydrate\Character\MailsHydrateBatch;
use Seatplus\Eveapi\Jobs\Hydrate\Character\SkillsHydrateBatch;
use Seatplus\Eveapi\Jobs\Hydrate\Character\WalletHydrateBatch;
use Seatplus\Eveapi\Jobs\Mail\MailHeaderJob;
use Seatplus\Eveapi\Jobs\Seatplus\UpdateCharacter;
use Seatplus\Eveapi\Jobs\Skills\SkillQueueJob;
use Seatplus\Eveapi\Jobs\Skills\SkillsJob;
use Seatplus\Eveapi\Jobs\Wallet\CharacterBalanceJob;
use Seatplus\Eveapi\Jobs\Wallet\CharacterWalletJournalJob;
use Seatplus\Eveapi\Jobs\Wallet\CharacterWalletTransactionJob;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Tests\TestCase;

uses(TestCase::class);

it('dispatches character info', function () {
    Bus::fake();

    (new UpdateCharacter)->handle();

    Bus::assertBatched(fn ($batch) => $batch->jobs->first(fn ($job) => $job instanceof CharacterInfoJob));
});

it('dispatch assets hydration job', function () {
    Bus::fake();

    (new UpdateCharacter)->handle();

    Bus::assertBatched(fn ($batch) => $batch->jobs->first(fn ($job) => $job instanceof CharacterAssetsHydrateBatch));
});

it('does not dispatch asset job for missing scopes', function () {
    Bus::fake();

    (new UpdateCharacter)->handle();

    $job_container = new JobContainer(['refresh_token' => $this->test_character->refresh_token]);

    $job = Mockery::mock(CharacterAssetsHydrateBatch::class . '[batch]', [$job_container]);

    $job->shouldNotReceive('batch');

    Bus::fake();

    $job->handle();

    //Bus::assertNotDispatched(CharacterAssetJob::class);
});

test('assets hydration job dispatches character assets job', function () {
    $refresh_token = Event::fakeFor(function () {
        return RefreshToken::factory()->create([
            'scopes' => ['esi-assets.read_assets.v1', 'esi-universe.read_structures.v1'],
        ]);
    });

    $job_container = new JobContainer(['refresh_token' => $refresh_token]);

    $job = Mockery::mock(CharacterAssetsHydrateBatch::class . '[batch]', [$job_container]);

    $batch = Mockery::mock(Batch::class)->makePartial();

    $batch->shouldReceive('add')->once()->with([
        [
            new CharacterAssetJob($job_container),
            new CharacterAssetsNameDispatchJob($job_container),
        ],
    ]);

    $job->shouldReceive('batch')
        ->once()->andReturn($batch);

    Bus::fake();

    $job->handle();
});

test('if constructor receives single refresh token push update to high queue', function () {
    $refresh_token = Event::fakeFor(function () {
        return RefreshToken::factory()->create([
            'scopes' => ['esi-assets.read_assets.v1', 'esi-universe.read_structures.v1'],
        ]);
    });

    Bus::fake();

    (new UpdateCharacter($refresh_token))->handle();

    Bus::assertBatched(fn (PendingBatchFake $batch) => $batch->queue() === 'high');
});

it('dispatches character role job', function () {
    $refresh_token = Event::fakeFor(function () {
        return RefreshToken::factory()->create([
            'scopes' => ['esi-characters.read_corporation_roles.v1'],
        ]);
    });

    Bus::fake();

    (new UpdateCharacter($refresh_token))->handle();

    Bus::assertBatched(fn ($batch) => $batch->jobs->first(fn ($job) => $job instanceof CharacterRolesHydrateBatch));
});

test('roles hydration job dispatches character roles job', function () {
    $refresh_token = Event::fakeFor(function () {
        return RefreshToken::factory()->create([
            'scopes' => ['esi-characters.read_corporation_roles.v1'],
        ]);
    });

    $job_container = new JobContainer(['refresh_token' => $refresh_token]);

    $job = Mockery::mock(CharacterRolesHydrateBatch::class . '[batch]', [$job_container]);

    $batch = Mockery::mock(Batch::class)->makePartial();

    $batch->shouldReceive('add')->once()->with([
        new CharacterRoleJob($job_container),
    ]);

    $job->shouldReceive('batch')
        ->once()->andReturn($batch);

    Bus::fake();

    $job->handle();
});

test('hydration does not dispatch role job for missing scopes', function () {
    Bus::fake();

    (new UpdateCharacter)->handle();

    $job_container = new JobContainer(['refresh_token' => $this->test_character->refresh_token]);

    $job = Mockery::mock(CharacterRolesHydrateBatch::class . '[batch]', [$job_container]);

    $job->shouldNotReceive('batch');

    Bus::fake();

    $job->handle();

    //Bus::assertNotDispatched(CharacterAssetJob::class);
});

test('character contact hydration adds no jobs to batch if scopes are missing', function () {
    $refresh_token = Event::fakeFor(function () {
        return RefreshToken::factory()->create([
            'scopes' => ['derp'],
        ]);
    });

    $job_container = new JobContainer(['refresh_token' => $refresh_token]);

    $job = Mockery::mock(ContactHydrateBatch::class . '[batch]', [$job_container]);

    $batch = Mockery::mock(Batch::class)->makePartial();

    $job->shouldNotReceive('batch');

    Bus::fake();

    $job->handle();
});

test('character contact hydration adds jobs to batch', function () {
    $refresh_token = Event::fakeFor(function () {
        return RefreshToken::factory()->create([
            'scopes' => ['esi-characters.read_contacts.v1'],
        ]);
    });

    $job_container = new JobContainer(['refresh_token' => $refresh_token]);

    $job = Mockery::mock(ContactHydrateBatch::class . '[batch]', [$job_container]);

    $batch = Mockery::mock(Batch::class)->makePartial();

    $batch->shouldReceive('add')->once()->with([
        [
            new CharacterContactJob($job_container),
            new CharacterContactLabelJob($job_container),
        ],
    ]);

    $job->shouldReceive('batch')
        ->once()->andReturn($batch);

    Bus::fake();

    $job->handle();
});

test('corporation contact hydration adds jobs to batch', function () {
    $refresh_token = Event::fakeFor(function () {
        return RefreshToken::factory()->create([
            'scopes' => ['esi-corporations.read_contacts.v1'],
        ]);
    });

    $job_container = new JobContainer(['refresh_token' => $refresh_token]);

    $job = Mockery::mock(ContactHydrateBatch::class . '[batch]', [$job_container]);

    $batch = Mockery::mock(Batch::class)->makePartial();

    $batch->shouldReceive('add')->once()->with([
        [
            new CorporationContactJob($job_container),
            new CorporationContactLabelJob($job_container),
        ],
    ]);

    $job->shouldReceive('batch')
        ->once()->andReturn($batch);

    Bus::fake();

    $job->handle();
});

test('alliance contact hydration adds jobs to batch', function () {
    $refresh_token = Event::fakeFor(function () {
        return RefreshToken::factory()->create([
            'scopes' => ['esi-alliances.read_contacts.v1'],
        ]);
    });

    $job_container = new JobContainer(['refresh_token' => $refresh_token]);

    $job = Mockery::mock(ContactHydrateBatch::class . '[batch]', [$job_container]);

    $batch = Mockery::mock(Batch::class)->makePartial();

    $batch->shouldReceive('add')->once()->with([
        [
            new AllianceContactJob($job_container),
            new AllianceContactLabelJob($job_container),
        ],
    ]);

    $job->shouldReceive('batch')
        ->once()->andReturn($batch);

    Bus::fake();

    $job->handle();
});

test('character wallet hydration adds jobs to batch', function () {
    $refresh_token = Event::fakeFor(function () {
        return RefreshToken::factory()->create([
            'scopes' => ['esi-wallet.read_character_wallet.v1'],
        ]);
    });

    $job_container = new JobContainer(['refresh_token' => $refresh_token]);

    $job = Mockery::mock(WalletHydrateBatch::class . '[batch]', [$job_container]);

    $batch = Mockery::mock(Batch::class)->makePartial();

    $batch->shouldReceive('add')->once()->with([
        new CharacterWalletJournalJob($job_container),
        new CharacterWalletTransactionJob($job_container),
        new CharacterBalanceJob($job_container),
    ]);

    $job->shouldReceive('batch')
        ->once()->andReturn($batch);

    Bus::fake();

    $job->handle();
});

test('character contract hydration adds jobs to batch', function () {
    $refresh_token = Event::fakeFor(function () {
        return RefreshToken::factory()->create([
            'scopes' => ['esi-contracts.read_character_contracts.v1'],
        ]);
    });

    $job_container = new JobContainer(['refresh_token' => $refresh_token]);

    $job = Mockery::mock(ContractHydrateBatch::class . '[batch]', [$job_container]);

    $batch = Mockery::mock(Batch::class)->makePartial();

    $batch->shouldReceive('add')->once()->with([
        new CharacterContractsJob($job_container),
    ]);

    $job->shouldReceive('batch')
        ->once()->andReturn($batch);

    Bus::fake();

    $job->handle();
});

it('dispatches corporation history job', function () {
    Bus::fake();

    (new UpdateCharacter)->handle();

    Bus::assertBatched(fn ($batch) => $batch->jobs->first(fn ($job) => $job instanceof CorporationHistoryJob));
});

it('dispatches skills job', function () {
    Bus::fake();

    (new UpdateCharacter)->handle();

    Bus::assertBatched(fn ($batch) => $batch->jobs->first(fn ($job) => $job instanceof SkillsHydrateBatch));
});

test('skills hydration adds skill jobs to batch', function () {
    $refresh_token = Event::fakeFor(function () {
        return RefreshToken::factory()->create([
            'scopes' => ['esi-skills.read_skills.v1'],
        ]);
    });

    $job_container = new JobContainer(['refresh_token' => $refresh_token]);

    $job = Mockery::mock(SkillsHydrateBatch::class . '[batch]', [$job_container]);

    $batch = Mockery::mock(Batch::class)->makePartial();

    $batch->shouldReceive('add')->once()->with([
        new SkillsJob($job_container),
    ]);

    $job->shouldReceive('batch')
        ->once()->andReturn($batch);

    Bus::fake();

    $job->handle();
});

test('skills hydration adds skill queue jobs to batch', function () {
    $refresh_token = Event::fakeFor(function () {
        return RefreshToken::factory()->create([
            'scopes' => ['esi-skills.read_skillqueue.v1'],
        ]);
    });

    $job_container = new JobContainer(['refresh_token' => $refresh_token]);

    $job = Mockery::mock(SkillsHydrateBatch::class . '[batch]', [$job_container]);

    $batch = Mockery::mock(Batch::class)->makePartial();

    $batch->shouldReceive('add')->once()->with([
        new SkillQueueJob($job_container),
    ]);

    $job->shouldReceive('batch')
        ->once()->andReturn($batch);

    Bus::fake();

    $job->handle();
});

test('mails hydration adds mail jobs to batch', function () {
    $refresh_token = Event::fakeFor(function () {
        return RefreshToken::factory()->create([
            'scopes' => ['esi-mail.read_mail.v1'],
        ]);
    });

    $job_container = new JobContainer(['refresh_token' => $refresh_token]);

    $job = Mockery::mock(MailsHydrateBatch::class . '[batch]', [$job_container]);

    $batch = Mockery::mock(Batch::class)->makePartial();

    $batch->shouldReceive('add')->once()->with([
        new MailHeaderJob($job_container),
    ]);

    $job->shouldReceive('batch')
        ->once()->andReturn($batch);

    Bus::fake();

    $job->handle();
});
