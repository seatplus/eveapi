<?php

/*
 * MIT License
 *
 * Copyright (c) 2019, 2020, 2021 Felix Huber
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

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
