<?php


namespace Seatplus\Eveapi\Tests\Integration;


use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Seat\Eseye\Containers\EsiResponse;
use Seatplus\Eveapi\Actions\Eseye\RetrieveEsiDataAction;
use Seatplus\Eveapi\Actions\Jobs\Wallet\CharacterWalletTransactionAction;
use Seatplus\Eveapi\Containers\JobContainer;
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

        $mock_data = $this->buildMockEsiData();

        $response = new EsiResponse($mock_data, [], 'now', 200);
        $response2 = new EsiResponse(json_encode([]), [], 'now', 200);

        $mock = Mockery::mock(CharacterWalletTransactionAction::class)->makePartial();

        $mock->shouldReceive('retrieve')
            ->once()
            ->ordered()
            ->andReturn($response);

        $mock->shouldReceive('retrieve')
            ->once()
            ->ordered()
            ->andReturn($response2);

        $mock->execute($this->test_character->refresh_token);

        $this->assertWalletTransaction($mock_data, $this->test_character->character_id);

        $this->assertEquals(['character_id' => $this->test_character->character_id], $mock->getPathValues());
        $this->assertEquals('esi-wallet.read_character_wallet.v1', $mock->getRequiredScope());
        $this->assertEquals($this->test_character->refresh_token, $mock->getRefreshToken());
        $this->assertEquals('get', $mock->getMethod());
        $this->assertEquals('/characters/{character_id}/wallet/transactions/', $mock->getEndpoint());
        $this->assertEquals('v1', $mock->getVersion());
    }

    /** @test */
    /*public function contact_of_type_character_dispatches_affiliation_job()
    {

        Queue::assertNothingPushed();

        $contact = Contact::factory()->create([
            'contactable_id' => $this->test_character->character_id,
            'contactable_type' => CharacterInfo::class,
            'contact_type' => 'character'
        ]);

        Queue::assertPushedOn('high', CharacterAffiliationJob::class);

    }*/

    private function buildMockEsiData()
    {

        $mock_data = WalletTransaction::factory()->count(5)->make();

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
        $mock = Mockery::mock('overload:' . RetrieveEsiDataAction::class);

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
