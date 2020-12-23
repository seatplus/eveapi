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

namespace Seatplus\Eveapi\Jobs\Contacts;

use Seatplus\Eveapi\Actions\Jobs\Character\CharacterRoleAction;
use Seatplus\Eveapi\Actions\Jobs\Contacts\AllianceContactAction;
use Seatplus\Eveapi\Actions\Jobs\Contacts\AllianceContactLabelAction;
use Seatplus\Eveapi\Actions\Jobs\Contacts\CharacterContactAction;
use Seatplus\Eveapi\Actions\Jobs\Contacts\CorporationContactAction;
use Seatplus\Eveapi\Actions\RetrieveFromEsiInterface;
use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Jobs\Middleware\EsiAvailabilityMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\EsiRateLimitedMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\HasRefreshTokenMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\HasRequiredScopeMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\RateLimitedJobMiddleware;

class AllianceContactLabelJob extends EsiBase
{
    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware(): array
    {
        $rate_limited_middleare = (new RateLimitedJobMiddleware)
            ->setKey(self::class)
            ->setViaCharacterId($this->refresh_token->character_id)
            ->setDuration(300);

        return [
            new HasRefreshTokenMiddleware,
            new HasRequiredScopeMiddleware,
            new EsiRateLimitedMiddleware,
            new EsiAvailabilityMiddleware,
            $rate_limited_middleare
        ];
    }

    public function tags(): array
    {
        return [
            'alliance',
            'alliance_id: ' . $this->alliance_id,
            'contacts',
            'labels'
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
        $this->getActionClass()->execute($this->refresh_token);
    }

    public function getActionClass(): RetrieveFromEsiInterface
    {
        return new AllianceContactLabelAction;
    }
}
