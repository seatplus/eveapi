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

namespace Seatplus\Eveapi\Jobs\Universe;

use Seatplus\Eveapi\Esi\HasPathValuesInterface;
use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Models\Universe\System;
use Seatplus\Eveapi\Traits\HasPathValues;

class ResolveUniverseSystemBySystemIdJob extends EsiBase implements HasPathValuesInterface
{
    use HasPathValues;

    public function __construct(
        private int $system_id
    ) {
        parent::__construct(
            method: 'get',
            endpoint: '/universe/systems/{system_id}/',
            version: 'v4',
        );

        $this->setPathValues([
            'system_id' => $system_id,
        ]);
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        return [
            ...parent::middleware(),
        ];
    }

    public function tags(): array
    {
        return [
            'system_resolve',
            'system_id:'.$this->system_id,
        ];
    }

    /**
     * Execute the job.
     */
    public function executeJob(): void
    {
        $response = $this->retrieve();

        System::firstOrCreate(
            ['system_id' => $response->system_id],
            [
                'constellation_id' => $response->constellation_id,
                'name' => $response->name,
                'security_status' => $response->security_status,

                'security_class' => data_get($response, 'security_class'),
            ]
        );
    }
}
