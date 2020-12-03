<?php

/*
 * MIT License
 *
 * Copyright (c) 2019, 2020 Felix Huber
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

namespace Seatplus\Eveapi\Actions\Jobs\Contacts;

use Illuminate\Support\Collection;
use Seatplus\Eveapi\Actions\HasPathValuesInterface;
use Seatplus\Eveapi\Actions\HasRequiredScopeInterface;
use Seatplus\Eveapi\Actions\RetrieveFromEsiBase;
use Seatplus\Eveapi\Actions\RetrieveFromEsiInterface;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Services\Contacts\ProcessContactResponse;

class CorporationContactAction extends RetrieveFromEsiBase implements RetrieveFromEsiInterface, HasPathValuesInterface, HasRequiredScopeInterface
{

    protected RefreshToken $refresh_token;

    private ?array $path_values;

    private int $page = 1;

    private Collection $known_ids;

    public function getMethod(): string
    {
        return 'get';
    }

    public function getEndpoint(): string
    {
        return '/corporations/{corporation_id}/contacts/';
    }

    public function getVersion(): string
    {
        return 'v2';
    }

    public function getRequiredScope(): string
    {
        return 'esi-corporations.read_contacts.v1';
    }

    public function execute(RefreshToken $refresh_token)
    {
        $this->refresh_token = $refresh_token;

        $this->setPathValues([
            'corporation_id' => $refresh_token->corporation->corporation_id,
        ]);

        $this->known_ids = collect();

        $corporation = $refresh_token->corporation;

        $processor = new ProcessContactResponse($corporation->corporation_id, CorporationInfo::class);

        while (true) {
            $response = $this->retrieve($this->page);

            if ($response->isCachedLoad()) {
                return;
            }

            $processed_ids = $processor->execute($response);

            $this->known_ids->push($processed_ids);

            // Lastly if more pages are present load next page
            if ($this->page >= $response->pages) {
                break;
            }

            $this->page++;
        }

        $processor->remove_old_contacts($this->known_ids->flatten()->unique()->toArray());
    }

    public function getRefreshToken(): RefreshToken
    {
        return $this->refresh_token;
    }

    public function setPathValues(array $array): void
    {
        $this->path_values = $array;
    }

    public function getPathValues(): array
    {
        return $this->path_values;
    }

}
