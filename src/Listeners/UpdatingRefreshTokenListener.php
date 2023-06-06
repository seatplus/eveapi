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

namespace Seatplus\Eveapi\Listeners;

use Firebase\JWT\JWT;
use Seatplus\Eveapi\Events\UpdatingRefreshTokenEvent;
use Seatplus\Eveapi\Jobs\Seatplus\UpdateCharacter;
use Seatplus\Eveapi\Jobs\Seatplus\UpdateCorporation;

class UpdatingRefreshTokenListener
{
    public function handle(UpdatingRefreshTokenEvent $refresh_token_event)
    {
        $refresh_token = $refresh_token_event->refresh_token;
        $original_scopes = $refresh_token->getOriginal('scopes');
        $new_scopes = $this->getScopes($refresh_token->token);

        if (array_diff($new_scopes, $original_scopes)) {
            UpdateCharacter::dispatch($refresh_token)->onQueue('high');

            $corporation_id = $refresh_token?->character?->corporation?->corporation_id;

            if ($corporation_id) {
                UpdateCorporation::dispatch($corporation_id)->onQueue('high');
            }
        }
    }

    private function getScopes(string $jwt)
    {
        $jwt_payload_base64_encoded = explode('.', $jwt)[1];

        $jwt_payload = JWT::urlsafeB64Decode($jwt_payload_base64_encoded);

        $scopes = data_get(json_decode($jwt_payload), 'scp', []);

        return is_array($scopes) ? $scopes : [$scopes];
    }
}
