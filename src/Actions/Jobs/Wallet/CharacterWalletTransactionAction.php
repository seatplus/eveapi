<?php


namespace Seatplus\Eveapi\Actions\Jobs\Wallet;


use Seatplus\Eveapi\Actions\HasPathValuesInterface;
use Seatplus\Eveapi\Actions\HasQueryStringInterface;
use Seatplus\Eveapi\Actions\HasRequiredScopeInterface;
use Seatplus\Eveapi\Actions\RetrieveFromEsiBase;
use Seatplus\Eveapi\Actions\RetrieveFromEsiInterface;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Wallet\WalletTransaction;
use Seatplus\Eveapi\Services\Wallet\ProcessWalletTransactionResponse;

class CharacterWalletTransactionAction extends RetrieveFromEsiBase implements RetrieveFromEsiInterface, HasPathValuesInterface, HasRequiredScopeInterface, HasQueryStringInterface
{

    private ?array $path_values;

    private array $query_string;

    protected RefreshToken $refresh_token;

    private int $from_id = PHP_INT_MAX;

    public function getPathValues(): array
    {
        return $this->path_values;
    }

    public function setPathValues(array $array): void
    {
        $this->path_values = $array;
    }

    public function getRequiredScope(): string
    {
        return 'esi-wallet.read_character_wallet.v1';
    }

    public function getRefreshToken(): RefreshToken
    {
        return $this->refresh_token;
    }

    public function getMethod(): string
    {
        return 'get';
    }

    public function getEndpoint(): string
    {
        return '/characters/{character_id}/wallet/transactions/';
    }

    public function getVersion(): string
    {
        return 'v1';
    }

    public function execute(RefreshToken $refresh_token)
    {
        $this->refresh_token = $refresh_token;

        $this->setPathValues([
            'character_id' => $refresh_token->character_id,
        ]);

        $processor = new ProcessWalletTransactionResponse(
            $this->refresh_token->character_id,
            CharacterInfo::class
        );

        $latest_transaction_id = WalletTransaction::where('wallet_transactionable_id', $this->refresh_token->character_id)
            ->latest()->first();

        if($latest_transaction_id)
            $this->from_id = $latest_transaction_id-1;

        while (true) {

            $this->setQueryString([
                'from_id' => $this->from_id
            ]);

            $response = $this->retrieve();

            if ($response->isCachedLoad() && !is_null($latest_transaction_id)) {
                return;
            }

            // If no more transactions are present, break the loop.
            if(collect($response)->isEmpty())
                break;

            $last_transaction_id = $processor->execute($response);

            $this->from_id = $last_transaction_id-1;
        }

    }


    public function getQueryString(): array
    {
        return $this->query_string;
    }

    public function setQueryString(array $array): void
    {
        $this->query_string = $array;
    }
}
