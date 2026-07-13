<?php

declare(strict_types=1);

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

namespace Seatplus\Eveapi\Listeners;

use Firebase\JWT\JWT;
use Seatplus\Eveapi\Events\UpdatingRefreshTokenEvent;
use Seatplus\Eveapi\Jobs\Seatplus\UpdateCharacter;
use Seatplus\Eveapi\Jobs\Seatplus\UpdateCorporation;

class UpdatingRefreshTokenListener
{
    public function handle(UpdatingRefreshTokenEvent $refreshTokenEvent): void
    {
        $refreshToken = $refreshTokenEvent->refreshToken;
        $originalScopes = $refreshToken->getOriginal('scopes');
        $newScopes = $this->getScopes($refreshToken->token);

        if (array_diff($newScopes, $originalScopes)) {
            // force: the scopes just changed — run a fresh batch even if a previous one is
            // in flight (or stuck), so the newly-granted endpoint (e.g. assets) is fetched now.
            UpdateCharacter::dispatch($refreshToken, force: true)->onQueue('high');

            $corporationId = data_get($refreshToken, 'character.corporation.corporation_id');

            if ($corporationId) {
                UpdateCorporation::dispatch($corporationId)->onQueue('high');
            }
        }
    }

    private function getScopes(string $jwt): array
    {
        $jwtPayloadBase64Encoded = explode('.', $jwt)[1];

        $jwtPayload = JWT::urlsafeB64Decode($jwtPayloadBase64Encoded);

        $scopes = data_get(json_decode($jwtPayload), 'scp', []);

        return is_array($scopes) ? $scopes : [$scopes];
    }
}
