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

namespace Seatplus\Eveapi\Actions\Jobs\Corporation;

use Seatplus\Eveapi\Actions\HasPathValuesInterface;
use Seatplus\Eveapi\Actions\HasRequiredScopeInterface;
use Seatplus\Eveapi\Actions\RetrieveFromEsiBase;
use Seatplus\Eveapi\Actions\RetrieveFromEsiInterface;
use Seatplus\Eveapi\Models\Character\CharacterRole;
use Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking;
use Seatplus\Eveapi\Models\RefreshToken;

class CorporationMemberTrackingAction extends RetrieveFromEsiBase implements RetrieveFromEsiInterface, HasPathValuesInterface, HasRequiredScopeInterface
{

    /**
     * @var string
     */
    protected $method = 'get';

    /**
     * @var string
     */
    protected $endpoint = '/corporations/{corporation_id}/membertracking/';

    /**
     * @var int
     */
    protected $version = 'v1';

    protected $refresh_token;

    public $required_scope = 'esi-corporations.track_members.v1';

    /**
     * @var array|null
     */
    private $path_values;

    public function execute(RefreshToken $refresh_token)
    {
        $this->refresh_token = $refresh_token;

        $this->setPathValues([
            'corporation_id' => $refresh_token->corporation_id,
        ]);

        $response = $this->retrieve();

        if ($response->isCachedLoad()) {
            return;
        }

        collect($response)
            ->lazy()
            ->each(fn($member) => CorporationMemberTracking::updateOrCreate([
                'corporation_id' => $refresh_token->corporation_id,
                'character_id'   => $member->character_id,
                ], [
                'start_date'   => property_exists($member, 'start_date') ? carbon($member->start_date) : null,
                'base_id'      => $member->base_id ?? null,
                'logon_date'   => property_exists($member, 'logon_date') ? carbon($member->logon_date) : null,
                'logoff_date'  => property_exists($member, 'logoff_date') ? carbon($member->logoff_date) : null,
                'location_id'  => $member->location_id ?? null,
                'ship_type_id' => $member->ship_type_id ?? null,
                ])
            )->pipe(fn($members) => CorporationMemberTracking::whereCorporationId($refresh_token->corporation_id)
                ->whereNotIn('character_id', $members->pluck('character_id')->all())->delete()
            );

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
