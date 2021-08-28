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

namespace Seatplus\Eveapi\Jobs\Mail;

use Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Esi\HasPathValuesInterface;
use Seatplus\Eveapi\Esi\HasRequiredScopeInterface;
use Seatplus\Eveapi\Jobs\NewEsiBase;
use Seatplus\Eveapi\Models\Mail\Mail;
use Seatplus\Eveapi\Traits\HasPathValues;
use Seatplus\Eveapi\Traits\HasRequiredScopes;

class MailBodyJob extends NewEsiBase implements HasPathValuesInterface, HasRequiredScopeInterface
{
    use HasPathValues;
    use HasRequiredScopes;

    public function __construct(JobContainer $job_container, int $mail_id)
    {
        $this->setJobType('character');
        parent::__construct($job_container);

        $this->setMethod('get');
        $this->setEndpoint('/characters/{character_id}/mail/{mail_id}/');
        $this->setVersion('v1');

        $this->setRequiredScope('esi-mail.read_mail.v1');

        $this->setPathValues([
            'character_id' => $this->getCharacterId(),
            'mail_id' => $mail_id,
        ]);
    }

    public function tags(): array
    {
        return [
            'mail',
            'body',
            sprintf('character_id:%s', data_get($this->getPathValues(), 'character_id')),
            sprintf('mail_id:%s', data_get($this->getPathValues(), 'mail_id')),
        ];
    }

    public function middleware(): array
    {
        return [
            (new ThrottlesExceptionsWithRedis(80, 5))
                ->by($this->uniqueId())
                ->when(fn () => ! $this->isEsiRateLimited())
                ->backoff(5),
        ];
    }

    public function handle(): void
    {
        $response = $this->retrieve();

        if ($response->isCachedLoad()) {
            return;
        }

        Mail::where('id', data_get($this->getPathValues(), 'mail_id'))
            ->update(['body' => data_get($response, 'body')]);
    }
}
