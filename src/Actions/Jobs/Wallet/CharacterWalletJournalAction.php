<?php


namespace Seatplus\Eveapi\Actions\Jobs\Wallet;


use Seatplus\Eveapi\Actions\HasPathValuesInterface;
use Seatplus\Eveapi\Actions\HasRequiredScopeInterface;
use Seatplus\Eveapi\Actions\RetrieveFromEsiBase;
use Seatplus\Eveapi\Actions\RetrieveFromEsiInterface;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Services\Wallet\ProcessWalletJournalResponse;

class CharacterWalletJournalAction extends RetrieveFromEsiBase implements RetrieveFromEsiInterface, HasPathValuesInterface, HasRequiredScopeInterface
{

    private ?array $path_values;

    protected RefreshToken $refresh_token;

    private int $page = 1;

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
        return '/characters/{character_id}/wallet/journal/';
    }

    public function getVersion(): string
    {
        return 'v6';
    }

    public function execute(RefreshToken $refresh_token)
    {
        $this->refresh_token = $refresh_token;

        $this->setPathValues([
            'character_id' => $refresh_token->character_id,
        ]);

        $processor = new ProcessWalletJournalResponse($this->refresh_token->character_id, CharacterInfo::class);

        while (true) {
            $response = $this->retrieve($this->page);

            if ($response->isCachedLoad()) {
                return;
            }

            $processor->execute($response);

            // Lastly if more pages are present load next page
            if ($this->page >= $response->pages) {
                break;
            }

            $this->page++;
        }

    }


}
