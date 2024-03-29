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

use Illuminate\Foundation\Bus\DispatchesJobs;
use Seatplus\Eveapi\Models\RefreshToken;

class DispatchIndividualUpdate
{
    use DispatchesJobs;

    public function __construct(
        private RefreshToken $refresh_token
    ) {
    }

    public function execute(string $job_name)
    {
        $job_class = config('eveapi.jobs')[$job_name];

        $id = $this->refresh_token->character_id;

        // check if job_name starts with corporation
        if (str_starts_with($job_name, 'corporation')) {
            $id = $this->refresh_token->character->corporation_id;
        }

        $job = (new $job_class($id))->onQueue('high');

        return $this->dispatch($job);
    }
}
