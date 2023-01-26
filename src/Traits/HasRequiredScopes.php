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

namespace Seatplus\Eveapi\Traits;

use Exception;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Services\FindCorporationRefreshToken;

trait HasRequiredScopes
{
    public string $required_scope;

    public function getRequiredScope(): string
    {
        return  $this->required_scope;
    }

    /**
     * @param string $required_scope
     */
    public function setRequiredScope(string $required_scope): void
    {
        $this->required_scope = $required_scope;
    }

    public function getRefreshToken(): RefreshToken
    {
        throw_unless($this->getRequiredScope(), new Exception('required scope is not set'));

        // create collection of potentially public properties of character_id, corporation_id and alliance_id
        $refresh_tokens = collect(get_object_vars($this))
            ->filter(fn ($value, $key) => in_array($key, ['character_id', 'corporation_id', 'alliance_id']))
            ->filter(fn ($value, $key) => $value !== null)
            // get refresh token for character_id, corporation_id or alliance_id
            ->map(function ($value, $key) {
                return match ($key) {
                    'character_id' => RefreshToken::firstWhere('character_id', $value),
                    'corporation_id' => $this->getCorporateRefreshToken($value),
                    'alliance_id' => $this->getAllianceRefreshToken($value),
                };
            });

        // throw error if length of collection is not 1
        if ($refresh_tokens->count() !== 1) {
            throw new Exception('Could not find refresh token for character_id, corporation_id or alliance_id');
        }

        $refresh_token = $refresh_tokens->first();

        #check if the refresh token has the required scope
        throw_unless($refresh_token->hasScope($this->getRequiredScope()), new Exception('refresh token does not have the required scope'));

        return $refresh_token;
    }

    private function getCorporateRefreshToken(int $corporation_id): RefreshToken
    {
        $roles = [];

        // if HasCorporationRoleInterface is implemented, get the role
        if (method_exists($this, 'getCorporationRoles')) {
            $roles = $this->getCorporationRoles();
        }

        $invoke = new FindCorporationRefreshToken;

        $refresh_token = $invoke($corporation_id, $this->getRequiredScope(), $roles);

        throw_unless($refresh_token, new Exception('Could not find refresh token for corporation_id'));

        return $refresh_token;
    }

    private function getAllianceRefreshToken(int $alliance_id): RefreshToken
    {
        // get all corporation_ids for the alliance
        $corporation_ids = CorporationInfo::query()->where('alliance_id', $alliance_id)->pluck('corporation_id');

        foreach ($corporation_ids as $corporation_id) {
            $refresh_token = $this->getCorporateRefreshToken($corporation_id);

            if ($refresh_token) {
                return $refresh_token;
            }
        }

        throw new Exception('Could not find refresh token for alliance_id');
    }
}
