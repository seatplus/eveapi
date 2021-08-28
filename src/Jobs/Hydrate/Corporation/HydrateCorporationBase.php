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

namespace Seatplus\Eveapi\Jobs\Hydrate\Corporation;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Services\FindCorporationRefreshToken;

abstract class HydrateCorporationBase implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @var JobContainer
     */
    public JobContainer $job_container;

    public function __construct(JobContainer $job_container)
    {
        $this->job_container = $this->enrichJobContainerWithRefreshToken($job_container);
    }

    abstract public function getRequiredScope(): string;

    abstract public function getRequiredRoles(): string | array;

    abstract public function handle();

    final public function enrichJobContainerWithRefreshToken(JobContainer $job_container): JobContainer
    {
        $find_corporation_refresh_token = new FindCorporationRefreshToken;

        $job_container->refresh_token = $find_corporation_refresh_token(
            $job_container->getCorporationId(),
            $this->getRequiredScope(),
            $this->getRequiredRoles()
        );

        return $job_container;
    }
}
