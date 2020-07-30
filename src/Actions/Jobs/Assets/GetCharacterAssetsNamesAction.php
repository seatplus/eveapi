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

namespace Seatplus\Eveapi\Actions\Jobs\Assets;

use Illuminate\Database\Eloquent\Builder;
use Seatplus\Eveapi\Actions\HasPathValuesInterface;
use Seatplus\Eveapi\Actions\HasRequestBodyInterface;
use Seatplus\Eveapi\Actions\HasRequiredScopeInterface;
use Seatplus\Eveapi\Actions\RetrieveFromEsiBase;
use Seatplus\Eveapi\Models\Assets\CharacterAsset;
use Seatplus\Eveapi\Models\RefreshToken;

class GetCharacterAssetsNamesAction extends RetrieveFromEsiBase implements HasPathValuesInterface, HasRequestBodyInterface, HasRequiredScopeInterface
{

    /**
     * @var string
     */
    protected $method = 'post';

    /**
     * @var string
     */
    protected $endpoint = '/characters/{character_id}/assets/names/';

    /**
     * @var string
     */
    protected $version = 'v1';

    public $required_scope = 'esi-assets.read_assets.v1';

    /**
     * @var \Seatplus\Eveapi\Models\RefreshToken
     */
    protected $refresh_token;

    /**
     * @var array|null
     */
    private $path_values;

    /**
     * @var array|null
     */
    private $request_body;

    public function execute(RefreshToken $refresh_token)
    {
        $this->refresh_token = $refresh_token;

        $this->setPathValues([
            'character_id' => $this->refresh_token->character_id,
        ]);

        CharacterAsset::whereHas('type.group', function (Builder $query) {
            // Only Celestials, Ships, Deployable, Starbases, Orbitals and Structures might be named
            $query->whereIn('category_id', [2, 6, 22, 23, 46, 65]);
        })->where('character_id', $this->refresh_token->character_id)
            ->select('item_id')
            ->where('is_singleton', true)
            ->pluck('item_id')
            ->filter(fn ($item_id) => is_null(cache()->store('file')->get($item_id)))
            ->chunk(1000)->each(function ($item_ids) {
                $this->setRequestBody($item_ids->all());

                $responses = $this->retrieve();

                collect($responses)->each(function ($response) {

                    // "None" seems to indicate that no name is set.
                    if ($response->name === 'None') {
                        return;
                    }

                    //cache items for 1 hrs
                    cache()->store('file')->put($response->item_id, $response->name, 3600);

                    CharacterAsset::where('character_id', $this->refresh_token->character_id)
                        ->where('item_id', $response->item_id)
                        ->update(['name' => $response->name]);
                });
            });
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getPathValues(): array
    {
        return $this->path_values;
    }

    public function getRequiredScope(): string
    {
        return $this->required_scope;
    }

    public function getRefreshToken(): RefreshToken
    {
        return $this->refresh_token;
    }

    public function setPathValues(array $array): void
    {
        $this->path_values = $array;
    }

    public function getRequestBody(): array
    {
        return $this->request_body;
    }

    public function setRequestBody(array $array): void
    {
        $this->request_body = $array;
    }
}
