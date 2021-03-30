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

namespace Seatplus\Eveapi\Jobs\Character;

use Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Esi\HasPathValuesInterface;
use Seatplus\Eveapi\Jobs\Middleware\EsiAvailabilityMiddleware;
use Seatplus\Eveapi\Jobs\NewEsiBase;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Traits\HasPathValues;

class CharacterInfoJob extends NewEsiBase implements HasPathValuesInterface
{
    use HasPathValues;

    public function __construct(?JobContainer $job_container = null)
    {
        $this->setJobType('character');
        parent::__construct($job_container);

        $this->setMethod('get');
        $this->setEndpoint('/characters/{character_id}/');
        $this->setVersion('v4');

        $this->setPathValues([
            'character_id' => $this->character_id,
        ]);
    }

    public function tags(): array
    {
        return [
            'character',
            'info',
            'character_id:' . $this->character_id,
        ];
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware(): array
    {
        return [
            new EsiAvailabilityMiddleware,
            (new ThrottlesExceptionsWithRedis(80, 5))
                ->by($this->uniqueId())
                ->when(fn () => ! $this->isEsiRateLimited())
                ->backoff(5),
        ];
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle(): void
    {
        $response = $this->retrieve();

        if ($response->isCachedLoad()) {
            return;
        }

        CharacterInfo::updateOrCreate([
            'character_id' => $this->character_id,
        ], [
            'name'            => $response->name,
            'description'     => $response->optional('description'),
            'birthday'        => $response->birthday,
            'gender'          => $response->gender,
            'race_id'         => $response->race_id,
            'bloodline_id'    => $response->bloodline_id,
            'ancestry_id'    => $response->optional('ancestry_id'),
            'security_status' => $response->optional('security_status'),
            'faction_id'      => $response->optional('faction_id'),
            'title' => $response->optional('title'),
        ]);
    }
}
