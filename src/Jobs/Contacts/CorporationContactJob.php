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

namespace Seatplus\Eveapi\Jobs\Contacts;

use Illuminate\Support\Collection;
use Seatplus\Eveapi\Jobs\Middleware\HasRequiredScopeMiddleware;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Services\Contacts\ProcessContactResponse;

class CorporationContactJob extends ContactBaseJob
{
    private int $page = 1;

    private Collection $known_ids;

    public function __construct(
        public int $corporation_id,
        public int $character_id
    ) {
        parent::__construct(
            method: 'get',
            endpoint: '/corporations/{corporation_id}/contacts/',
            version: 'v2',
        );

        $this->setRequiredScope('esi-corporations.read_contacts.v1');

        $this->setPathValues([
            'corporation_id' => $this->corporation_id,
        ]);

        $this->known_ids = collect();
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        return [
            new HasRequiredScopeMiddleware,
            ...parent::middleware(),
        ];
    }

    public function tags(): array
    {
        return [
            'corporation',
            'corporation_id: '.$this->corporation_id,
            'contacts',
        ];
    }

    /**
     * Execute the job.
     *
     * @throws \Exception
     */
    public function executeJob(): void
    {
        $processor = new ProcessContactResponse($this->corporation_id, CorporationInfo::class);

        while (true) {
            $response = $this->retrieve($this->page);

            if ($response->isCachedLoad()) {
                return;
            }

            $processed_ids = $processor->execute($response);

            $this->known_ids->push($processed_ids);

            // Lastly if more pages are present load next page
            if ($this->page >= $response->pages) {
                break;
            }

            $this->page++;
        }

        $processor->remove_old_contacts($this->known_ids->flatten()->unique()->toArray());
    }
}
