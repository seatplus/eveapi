<?php


namespace Seatplus\Eveapi\Tests\Integration;


use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Wallet\CorporationWalletJournalByDivisionJob;
use Seatplus\Eveapi\Jobs\Wallet\CorporationWalletJournalJob;
use Seatplus\Eveapi\Jobs\Corporation\CorporationWalletsJob;
use Seatplus\Eveapi\Jobs\Wallet\CorporationWalletTransactionByDivisionJob;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationWallet;
use Seatplus\Eveapi\Models\Wallet\WalletJournal;
use Seatplus\Eveapi\Models\Wallet\WalletTransaction;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

class CorporationWalletLifeCycleTest extends TestCase
{

    use MockRetrieveEsiDataAction;

    private JobContainer $job_container;

    public function setUp(): void
    {
        parent::setUp();

        $refresh_token = Event::fakeFor(function () {
            $token = $this->test_character->refresh_token;
            $token->scopes = ['esi-wallet.read_corporation_wallets.v1'];
            $token->save();

            return $token;
        });

        $this->job_container = new JobContainer(['refresh_token' => $refresh_token]);
    }

    /** @test */
    public function itRunsTheJob()
    {
        $this->buildCorpWalletEsiMockData();

        $this->assertCount(0, CorporationWallet::all());

        // Prevent Observer from picking up the jobs
        Event::fake();

        CorporationWalletsJob::dispatchSync($this->job_container);

        $this->assertCount(7, CorporationWallet::all());
    }

    /** @test */
    public function itHasObserverAndDispatchesJob()
    {
        $character_roles = $this->test_character->roles;
        $character_roles->roles = ['Accountant'];
        $character_roles->save();

        Queue::fake();

        CorporationWallet::factory()->count(1)->create([
            'corporation_id' => $this->test_character->corporation->corporation_id
        ]);

        Queue::assertPushed(CorporationWalletJournalByDivisionJob::class);
        Queue::assertPushed(CorporationWalletTransactionByDivisionJob::class);
    }

    /** @test */
    public function itSpawnsAJobForEveryWalletDivision()
    {

        $character_roles = $this->test_character->roles;
        $character_roles->roles = ['Accountant'];
        $character_roles->save();

        Queue::fake();

        Event::fakeFor( fn () => CorporationWallet::factory()->count(7)->create([
            'corporation_id' => $this->test_character->corporation->corporation_id
        ]));

        (new CorporationWalletJournalJob($this->job_container))->handle();

        $this->assertCount(7, CorporationWallet::all());

        Queue::assertPushed(CorporationWalletJournalByDivisionJob::class);
    }

    /** @test */
    public function itCreatesWalletJournalEntries()
    {
        $this->test_character->refresh_token()->update(['scopes' => config('eveapi.scopes.corporation.wallet')]);
        $this->test_character->roles()->update(['roles' => ['Accountant']]);

        $corporation = $this->test_character->corporation;

        $this->assertCount(0, $corporation->wallet_journals);

        $mock_data = $this->buildCorporationWalletJournaltEsiMockData();

        CorporationWalletJournalByDivisionJob::dispatchSync($this->job_container, $mock_data->first()->division);

        $corporation = $corporation->refresh();
        $this->assertCount($mock_data->count(), $corporation->wallet_journals);
        $this->assertInstanceOf(WalletJournal::class, $corporation->wallet_journals->first());
    }

    /** @test */
    public function itCreatesWalletTransactionEntries()
    {
        $this->test_character->refresh_token()->update(['scopes' => config('eveapi.scopes.corporation.wallet')]);
        $this->test_character->roles()->update(['roles' => ['Accountant']]);

        $corporation = $this->test_character->corporation;

        $this->assertCount(0, $corporation->wallet_transactions);

        $mock_data = $this->buildCorporationWalletTransactionEsiMockData();

        // Prevent Observers to pickup any new jobs
        Event::fake();

        CorporationWalletTransactionByDivisionJob::dispatchSync($this->job_container, $mock_data->first()->division);

        $corporation = $corporation->refresh();
        $this->assertCount($mock_data->count(), $corporation->wallet_transactions);
        $this->assertInstanceOf(WalletTransaction::class, $corporation->wallet_transactions->first());
    }

    private function buildCorpWalletEsiMockData()
    {

        $mock_data = CorporationWallet::factory()->count(7)->make([
            'corporation_id' => $this->test_character->corporation->corporation_id
        ]);

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        return $mock_data;
    }

    private function buildCorporationWalletJournaltEsiMockData()
    {

        $mock_data = WalletJournal::factory()->count(7)->make([
            'wallet_journable_id' => $this->test_character->corporation->corporation_id,
            'wallet_journable_type' => CorporationInfo::class,
            'division' => 3
        ]);

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        return $mock_data;
    }

    private function buildCorporationWalletTransactionEsiMockData()
    {

        $mock_data = WalletTransaction::factory()->count(7)->make([
            'wallet_transactionable_id' => $this->test_character->corporation->corporation_id,
            'wallet_transactionable_type' => CorporationInfo::class,
            'division' => 3
        ]);

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        return $mock_data;
    }


}