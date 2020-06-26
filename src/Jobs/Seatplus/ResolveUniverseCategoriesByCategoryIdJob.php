<?php

/*
 * MIT License
 *
 * Copyright (c) 2019, 2020 Felix Huber
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

namespace Seatplus\Eveapi\Jobs\Seatplus;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Seatplus\Eveapi\Actions\Jobs\Universe\ResolveUniverseCategoriesByCategoryIdAction;
use Seatplus\Eveapi\Jobs\Middleware\EsiAvailabilityMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\EsiRateLimitedMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\RedisFunnelMiddleware;

class ResolveUniverseCategoriesByCategoryIdJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    private ?int $category_id;

    public function __construct(?int $category_id = null)
    {

        $this->category_id = $category_id;
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware(): array
    {
        return [
            new EsiRateLimitedMiddleware,
            new EsiAvailabilityMiddleware,
            new RedisFunnelMiddleware,
        ];
    }

    public function tags(): array
    {

        return [
            'type',
            'informations',
            sprintf('category_id:%s', $this->category_id ?? ''),
        ];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        (new ResolveUniverseCategoriesByCategoryIdAction)->execute($this->category_id);

    }
}
