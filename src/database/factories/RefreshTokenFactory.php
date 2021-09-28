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

namespace Seatplus\Eveapi\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Seatplus\Eveapi\Models\RefreshToken;

class RefreshTokenFactory extends Factory
{
    protected $model = RefreshToken::class;

    public function definition()
    {
        return [
            'character_id' => $this->faker->numberBetween(9000000, 98000000),
            'refresh_token' => 'MmLZC2vwExCby2vbdgEVpOxXPUG3mIGfkQM5gl9IPtA',
            'expires_on' => now()->addMinutes(10),
            'token' => json_encode([
                "scp" => [
                    "esi-skills.read_skills.v1",
                    "esi-skills.read_skillqueue.v1",
                ],
                "jti" => "998e12c7-3241-43c5-8355-2c48822e0a1b",
                "kid" => "JWT-Signature-Key",
                "sub" => "CHARACTER:EVE:123123",
                "azp" => "my3rdpartyclientid",
                "name" => "Some Bloke",
                "owner" => "8PmzCeTKb4VFUDrHLc/AeZXDSWM=",
                "exp" => 1534412504,
                "iss" => "login.eveonline.com",
            ]),
        ];
    }

    public function scopes(array $scopes)
    {
        return $this->state(function (array $attributes) use ($scopes) {
            $token = json_decode(data_get($attributes, 'token'), true);
            data_set($token, 'scp', $scopes);

            return [
                'token' => json_encode($token)
            ];

        });

    }
}
