<?php


namespace Seatplus\Eveapi\Tests\Integration;


use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Seat\Eseye\Containers\EsiResponse;
use Seatplus\Eveapi\Services\Esi\RetrieveEsiData;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Jobs\Wallet\CharacterWalletTransactionJob;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Wallet\WalletTransaction;
use Seatplus\Eveapi\Tests\TestCase;

class CharacterWalletTransactionLifecycleTest extends TestCase
{

    public JobContainer $job_container;

    public function setUp(): void
    {
        parent::setUp();

        // Prevent any auto dispatching of jobs
        Queue::fake();

        $this->job_container = new JobContainer(['refresh_token' => $this->test_character->refresh_token]);
    }

    /** @test */
    public function runWalletTransactionAction()
    {
        Queue::assertNothingPushed();
        $mock_data = $this->buildMockEsiData();
        Queue::assertNothingPushed();

        $response = new EsiResponse($mock_data, [], 'now', 200);
        $response2 = new EsiResponse(json_encode([]), [], 'now', 200);

        $job_container = new JobContainer(['refresh_token' => $this->test_character->refresh_token]);
        $mock = Mockery::mock(CharacterWalletTransactionJob::class)->makePartial();

        $mock->shouldReceive('getCharacterId')
            ->andReturn($this->test_character->character_id);

        $mock->shouldReceive('retrieve')
            ->once()
            ->ordered()
            ->andReturn($response);

        $mock->shouldReceive('retrieve')
            ->once()
            ->ordered()
            ->andReturn($response2);

        $mock->handle();

        $this->assertWalletTransaction($mock_data, $this->test_character->character_id);
    }

    /** @test */
    public function creationOfCharacterWalletTransactionDispatchesJobs()
    {

        $wallet_transaction = WalletTransaction::factory()->create([
            'wallet_transactionable_id' => $this->test_character->character_id
        ]);

        Queue::assertPushedOn('high', ResolveLocationJob::class);
        Queue::assertPushedOn('high', ResolveUniverseTypeByIdJob::class);
    }

    /** @test */
    public function creationOfCorporationWalletTransactionDispatchesJobs()
    {

        $wallet_transaction = WalletTransaction::factory()->create([
            'wallet_transactionable_id' => $this->test_character->corporation->corporation_id,
            'wallet_transactionable_type' => CorporationInfo::class
        ]);

        Queue::assertPushedOn('high', ResolveLocationJob::class);
        Queue::assertPushedOn('high', ResolveUniverseTypeByIdJob::class);
    }

    private function buildMockEsiData()
    {

        $mock_data = Event::fakeFor(fn() => WalletTransaction::factory()->count(5)->make());

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        return $mock_data;
    }

    private function assertWalletTransaction(Collection $mock_data, int $wallet_transactionable_id)
    {
        foreach ($mock_data as $data)
            //Assert that character asset created
            $this->assertDatabaseHas('wallet_transactions', [
                'wallet_transactionable_id' => $wallet_transactionable_id,
                'transaction_id' => $data->transaction_id
            ]);
    }

    private function mockRetrieveEsiDataAction(array $body) : void
    {

        $data = json_encode($body);

        $response = new EsiResponse($data, [], 'now', 200);
        $response2 = new EsiResponse(json_encode([]), [], 'now', 200);

        //$mock = Mockery::namedMock( CharacterWalletTransactionAction::class,  RetrieveFromEsiInterface::class)->makePartial();
        $mock = Mockery::mock('overload:' . RetrieveEsiData::class);

        $mock->shouldReceive('execute')
            ->once()
            ->ordered()
            ->andReturn($response);

        $mock->shouldReceive('execute')
            ->once()
            ->ordered()
            ->andReturn($response2);
    }
}
