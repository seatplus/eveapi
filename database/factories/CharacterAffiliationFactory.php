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

namespace Seatplus\Eveapi\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Seatplus\Eveapi\Models\Character\CharacterAffiliation>
 */
class CharacterAffiliationFactory extends Factory
{
    protected $model = CharacterAffiliation::class;

    #[\Override]
    public function definition(): array
    {
        return [
            'character_id' => fake()->numberBetween(9000000, 98000000),
            'corporation_id' => fake()->numberBetween(98000000, 99000000),
            'alliance_id' => fake()->optional()->numberBetween(99000000, 100000000),
            'faction_id' => fake()->optional()->numberBetween(500000, 1000000),
            'last_pulled' => fake()->dateTime(),
        ];
    }

    public function withAlliance(): CharacterAffiliationFactory
    {
        return $this->state(fn () => [
            'alliance_id' => AllianceInfo::factory(),
        ]);
    }

    #[\Override]
    public function configure()
    {
        return $this->afterCreating(function (CharacterAffiliation $character_affiliation) {

            // if alliance_id is set, update alliance of corporation
            if ($character_affiliation->alliance_id) {

                // first check if corporation exists
                $corporation = CorporationInfo::find($character_affiliation->corporation_id);
                if ($corporation) {
                    $corporation->alliance_id = $character_affiliation->alliance_id;
                    $corporation->save();
                } else {
                    // if corporation does not exist, create it
                    CorporationInfo::factory()->create([
                        'corporation_id' => $character_affiliation->corporation_id,
                        'alliance_id' => $character_affiliation->alliance_id,
                    ]);
                }
            }

        });
    }
}
