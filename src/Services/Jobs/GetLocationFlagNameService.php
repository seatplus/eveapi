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

namespace Seatplus\Eveapi\Services\Jobs;

class GetLocationFlagNameService
{
    public function __construct()
    {
    }

    public static function make()
    {
        return new static();
    }

    public function get(int $flag_id): string
    {
        $location_flags = [
            'highslots' => range(27, 34),
            'midslots' => range(19, 26),
            'lowslots' => range(11, 18),
            'rigslots' => range(92, 99),
            'subsystems' => range(125, 132),
            'fleet_hangar' => range(155, 155),
            'ship_hangar' => range(90, 90),
            'fighter_tubes' => range(159, 163),
            'dronebay' => [
                ...range(158, 158), //FighterBay
                ...range(87, 87), // DroneBay
            ],
            'specialized' => [
                ...range(133, 143),
                ...range(148, 149),
                ...range(151, 151),
            ],
        ];

        foreach ($location_flags as $location_flag => $ids) {
            if (in_array($flag_id, $ids)) {
                return $location_flag;
            }
        }

        return 'everything_else';
    }
}
