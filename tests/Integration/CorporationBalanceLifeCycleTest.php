<?php


use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Wallet\CorporationBalanceJob;
use Seatplus\Eveapi\Jobs\Wallet\CorporationWalletJournalByDivisionJob;
use Seatplus\Eveapi\Jobs\Wallet\CorporationWalletJournalJob;
use Seatplus\Eveapi\Jobs\Wallet\CorporationWalletTransactionByDivisionJob;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Wallet\Balance;
use Seatplus\Eveapi\Models\Wallet\WalletJournal;
use Seatplus\Eveapi\Models\Wallet\WalletTransaction;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

uses(TestCase::class);
uses(MockRetrieveEsiDataAction::class);

beforeEach(function () {
    $refresh_token = Event::fakeFor(function () {
        $token = $this->test_character->refresh_token;
        $token->scopes = ['esi-wallet.read_corporation_wallets.v1'];
        $token->save();

        return $token;
    });

    $this->job_container = new JobContainer(['refresh_token' => $refresh_token]);
});

it('runs the job', function () {
    buildCorpWalletEsiMockData();

    $this->assertCount(0, Balance::all());

    // Prevent Observer from picking up the jobs
    Event::fake();

    (new CorporationBalanceJob($this->job_container))->handle();

    $this->assertCount(7, Balance::all());
});

it('has observer and dispatches job', function () {
    $character_roles = $this->test_character->roles;
    $character_roles->roles = ['Accountant'];
    $character_roles->save();

    Queue::fake();

    $balances = Balance::factory()
        ->withDivision()
        ->count(7)
        ->create([
            'balanceable_id' => $this->test_character->corporation->corporation_id,
            'balanceable_type' => CorporationInfo::class,
        ]);

    Queue::assertPushed(CorporationWalletJournalByDivisionJob::class);
    Queue::assertPushed(CorporationWalletTransactionByDivisionJob::class);
});

it('spawns a job for every wallet division', function () {
    $character_roles = $this->test_character->roles;
    $character_roles->roles = ['Accountant'];
    $character_roles->save();

    Queue::fake();

    Event::fakeFor(fn () => Balance::factory()->withDivision()->count(7)->create([
        'balanceable_id' => $this->test_character->corporation->corporation_id,
        'balanceable_type' => CorporationInfo::class,
    ]));

    (new CorporationWalletJournalJob($this->job_container))->handle();

    $this->assertCount(7, Balance::all());

    Queue::assertPushed(CorporationWalletJournalByDivisionJob::class);
});

it('creates wallet journal entries', function () {
    $this->test_character->refresh_token()->update(['scopes' => config('eveapi.scopes.corporation.wallet')]);
    $this->test_character->roles()->update(['roles' => ['Accountant']]);

    $corporation = $this->test_character->corporation;

    $this->assertCount(0, $corporation->wallet_journals);

    $mock_data = buildCorporationWalletJournaltEsiMockData();

    CorporationWalletJournalByDivisionJob::dispatchSync($this->job_container, $mock_data->first()->division);

    $corporation = $corporation->refresh();
    $this->assertCount($mock_data->count(), $corporation->wallet_journals);
    $this->assertInstanceOf(WalletJournal::class, $corporation->wallet_journals->first());
});

it('creates wallet transaction entries', function () {
    $this->test_character->refresh_token()->update(['scopes' => config('eveapi.scopes.corporation.wallet')]);
    $this->test_character->roles()->update(['roles' => ['Accountant']]);

    $corporation = $this->test_character->corporation;

    $this->assertCount(0, $corporation->wallet_transactions);

    $mock_data = buildCorporationWalletTransactionEsiMockData();

    // Prevent Observers to pickup any new jobs
    Event::fake();

    CorporationWalletTransactionByDivisionJob::dispatchSync($this->job_container, $mock_data->first()->division);

    $corporation = $corporation->refresh();
    $this->assertCount($mock_data->count(), $corporation->wallet_transactions);
    $this->assertInstanceOf(WalletTransaction::class, $corporation->wallet_transactions->first());
});

// Helpers
function buildCorpWalletEsiMockData()
{
    $mock_data = Balance::factory()->withDivision()->count(7)->make([
        'balanceable_id' => $this->test_character->corporation->corporation_id,
        'balanceable_type' => CorporationInfo::class,
    ]);

    $this->mockRetrieveEsiDataAction($mock_data->toArray());

    return $mock_data;
}

function buildCorporationWalletJournaltEsiMockData()
{
    $mock_data = WalletJournal::factory()->count(7)->make([
        'wallet_journable_id' => $this->test_character->corporation->corporation_id,
        'wallet_journable_type' => CorporationInfo::class,
        'division' => 3,
    ]);

    $this->mockRetrieveEsiDataAction($mock_data->toArray());

    return $mock_data;
}

function buildCorporationWalletTransactionEsiMockData()
{
    $mock_data = WalletTransaction::factory()->count(7)->make([
        'wallet_transactionable_id' => $this->test_character->corporation->corporation_id,
        'wallet_transactionable_type' => CorporationInfo::class,
        'division' => 3,
    ]);

    $this->mockRetrieveEsiDataAction($mock_data->toArray());

    return $mock_data;
}
