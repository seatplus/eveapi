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

namespace Seatplus\Eveapi\Services;

use Seatplus\Eveapi\Models\RefreshToken;

class FindCorporationRefreshToken
{
    /**
     * @param  string  $role
     */
    public function __invoke(int $corporation_id, string|array $scope, string|array $role): ?RefreshToken
    {
        $scopes = is_string($scope) ? [$scope] : (is_array($scope) ? $scope : []);
        $roles = is_string($role) ? [$role] : (is_array($role) ? $role : []);

        return RefreshToken::with('corporation', 'character.roles')
            ->whereHas('corporation', fn ($query) => $query->where('corporation_infos.corporation_id', $corporation_id))
            ->cursor()
            ->shuffle()
            ->filter(function (RefreshToken $token) use ($scopes) {
                foreach ($scopes as $scope) {
                    if ($token->hasScope($scope)) {
                        return true;
                    }
                }

                return false;
            })
            ->first(function ($token) use ($roles) {
                // if no roles are given, return the first token with the correct scope
                if (empty($roles)) {
                    return true;
                }

                foreach ($roles as $role) {
                    if ($token->character?->roles?->hasRole('roles', $role)) {
                        return true;
                    }
                }

                return false;
            });
    }
}
