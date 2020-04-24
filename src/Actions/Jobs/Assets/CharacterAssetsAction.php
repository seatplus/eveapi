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

use Illuminate\Support\Collection;
use Seatplus\Eveapi\Actions\Character\CharacterAssetsCleanupAction;
use Seatplus\Eveapi\Actions\HasPathValuesInterface;
use Seatplus\Eveapi\Actions\HasRequiredScopeInterface;
use Seatplus\Eveapi\Actions\RetrieveFromEsiBase;
use Seatplus\Eveapi\Models\Assets\CharacterAsset;
use Seatplus\Eveapi\Models\RefreshToken;

class CharacterAssetsAction extends RetrieveFromEsiBase implements HasPathValuesInterface, HasRequiredScopeInterface
{

    /**
     * @var string
     */
    protected $method = 'get';

    /**
     * @var string
     */
    protected $endpoint = '/characters/{character_id}/assets/';

    /**
     * @var string
     */
    protected $version = 'v5';

    public $required_scope = 'esi-assets.read_assets.v1';

    /**
     * @var \Illuminate\Support\Collection|null
     */
    private $known_assets;

    /**
     * @var \Seatplus\Eveapi\Models\RefreshToken
     */
    protected $refresh_token;

    /**
     * @var int
     */
    private $page = 1;

    public function execute(RefreshToken $refresh_token)
    {

        $this->refresh_token = $refresh_token;
        $this->known_assets = collect();

        while (true)
        {
            $response = $this->retrieve($this->page);

            if ($response->isCachedLoad()) return;

            // First update the
            collect($response)->each(function ($asset) {

                CharacterAsset::updateOrCreate([
                    'item_id' => $asset->item_id,
                ], [
                    'character_id' => $this->refresh_token->character_id,
                    'is_blueprint_copy' => optional($asset)->is_blueprint_copy ?? false,
                    'is_singleton'  => $asset->is_singleton,
                    'location_flag'     => $asset->location_flag,
                    'location_id'        => $asset->location_id,
                    'location_type'          => $asset->location_type,
                    'quantity'   => $asset->quantity,
                    'type_id' => $asset->type_id,
                ]);

            })->pipe(function (Collection $response) {

                return $response->pluck('item_id')->each(function ($id) {

                    $this->known_assets->push($id);
                });
            });

            // Lastly if more pages are present load next page
            if($this->page >= $response->pages)
                break;

            $this->page++;
        }

        // Cleanup old items
        (new CharacterAssetsCleanupAction)->execute($this->refresh_token->character_id, $this->known_assets->toArray());

        //Get Names for the items
        (new GetCharacterAssetsNamesAction)->execute($this->refresh_token);

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
        return [
            'character_id' => $this->refresh_token->character_id,
        ];
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

    }
}
