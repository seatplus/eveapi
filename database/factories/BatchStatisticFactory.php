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
use Seatplus\Eveapi\Models\BatchStatistic;

class BatchStatisticFactory extends Factory
{
    protected $model = BatchStatistic::class;

    public function definition()
    {

        // get app env from config
        $env = config('app.env');

        // get horizon config
        $horizon_config = config("horizon.environments.${env}.seatplus-workers");

        return [
            'batch_id' => $this->faker->uuid(),
            'batch_name' => $this->faker->name(),
            'total_jobs' => $this->faker->numberBetween(1, 100),
            'queue_balancing_configuration' => json_encode($horizon_config),
        ];
    }

    public function finished(): Factory
    {
        return $this->state(function () {

            $started_at = $this->faker->dateTimeBetween('-1 week', 'now');
            $duration = $this->faker->numberBetween(3, 42);

            return [
                'started_at' => $started_at,
                'finished_at' => carbon($started_at)->addMinutes($duration),
            ];
        });
    }
}
