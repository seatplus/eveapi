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
use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Character\CharacterRole;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\RefreshToken;

class CharacterInfoFactory extends Factory
{
    protected $model = CharacterInfo::class;

    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterCreating(function (CharacterInfo $character_info) {
            $character_affiliation = $character_info
                ->character_affiliation()
                ->save(CharacterAffiliation::factory()->withAlliance()->create());

            /*$alliance = AllianceInfo::factory()->create([
                'alliance_id' => $character_affiliation->alliance_id
            ]);*/

            $character_affiliation->corporation()->associate(CorporationInfo::factory()->create([
                'corporation_id' => $character_affiliation->corporation_id,
                'alliance_id' => $character_affiliation->alliance_id,
            ]));

            //$character_affiliation->alliance()->associate($alliance);

            Event::fakeFor(function () use ($character_info) {
                $character_info->refresh_token()->save(RefreshToken::factory()->create());
            });

            $character_info->roles()->save(CharacterRole::factory()->create());
        });
    }

    public function definition()
    {
        return [
            'character_id' => $this->faker->numberBetween(9000000, 98000000),
            'name' => $this->faker->name,
            //'corporation_id'  => $this->faker->numberBetween(98000000, 99000000),
            'birthday' => $this->faker->iso8601($max = 'now'),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'race_id' => $this->faker->randomDigitNotNull,
            'bloodline_id' => $this->faker->randomDigitNotNull,
            'description' => $this->faker->optional()->realText(),
            'security_status' => $this->faker->optional()->randomFloat(null, -10, +10),
            'title' => $this->faker->optional()->bs,
        ];
    }
}
