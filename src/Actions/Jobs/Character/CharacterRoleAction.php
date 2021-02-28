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

namespace Seatplus\Eveapi\Actions\Jobs\Character;

use Seatplus\Eveapi\Actions\HasPathValuesInterface;
use Seatplus\Eveapi\Actions\HasRequiredScopeInterface;
use Seatplus\Eveapi\Actions\RetrieveFromEsiBase;
use Seatplus\Eveapi\Actions\RetrieveFromEsiInterface;
use Seatplus\Eveapi\Models\Character\CharacterRole;
use Seatplus\Eveapi\Models\RefreshToken;

class CharacterRoleAction extends RetrieveFromEsiBase implements RetrieveFromEsiInterface, HasPathValuesInterface, HasRequiredScopeInterface
{

    /**
     * @var string
     */
    protected $method = 'get';

    /**
     * @var string
     */
    protected $endpoint = '/characters/{character_id}/roles/';

    /**
     * @var int
     */
    protected $version = 'v2';

    protected $refresh_token;

    public $required_scope = 'esi-characters.read_corporation_roles.v1';

    /**
     * @var array|null
     */
    private $path_values;

    public function execute(RefreshToken $refresh_token)
    {
        $this->refresh_token = $refresh_token;

        $this->setPathValues([
            'character_id' => $refresh_token->character_id,
        ]);

        $response = $this->retrieve();

        if ($response->isCachedLoad()) {
            return;
        }

        CharacterRole::updateOrCreate([
            'character_id' => $refresh_token->character_id,
        ], [
            'roles' => $response->roles,
            'roles_at_base' => $response->roles_at_base,
            'roles_at_hq' => $response->roles_at_hq,
            'roles_at_other' => $response->roles_at_other,
        ]);
    }

    public function getRequiredScope(): string
    {
        return $this->required_scope;
    }

    public function getRefreshToken(): RefreshToken
    {
        return $this->refresh_token;
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

    public function setPathValues(array $array): void
    {
        $this->path_values = $array;
    }
}
