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
use Illuminate\Support\Collection;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Esi\HasPathValuesInterface;
use Seatplus\Eveapi\Esi\HasRequiredScopeInterface;
use Seatplus\Eveapi\Jobs\NewEsiBase;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Mail\Mail;
use Seatplus\Eveapi\Models\Mail\MailRecipients;
use Seatplus\Eveapi\Traits\HasPathValues;
use Seatplus\Eveapi\Traits\HasRequiredScopes;

class MailHeaderJob extends NewEsiBase implements HasPathValuesInterface, HasRequiredScopeInterface
{
    use HasPathValues;
    use HasRequiredScopes;

    public function __construct(protected JobContainer $job_container)
    {
        $this->setJobType('character');
        parent::__construct($job_container);

        $this->setMethod('get');
        $this->setEndpoint('/characters/{character_id}/mail/');
        $this->setVersion('v1');

        $this->setRequiredScope('esi-mail.read_mail.v1');

        $this->setPathValues([
            'character_id' => $this->getCharacterId(),
        ]);
    }

    public function tags(): array
    {
        return [
            'mail',
            'header',
            sprintf('character_id:%s', data_get($this->getPathValues(), 'character_id')),
        ];
    }

    public function middleware(): array
    {
        return [
            (new ThrottlesExceptionsWithRedis(80, 5))
                ->by('esiratelimit')
                ->backoff(5),
        ];
    }

    public function handle(): void
    {
        $response = $this->retrieve();

        if ($response->isCachedLoad()) {
            return;
        }

        collect($response)
            ->map(fn ($mail) => [
                'id' => data_get($mail, 'mail_id'),
                'subject' => data_get($mail, 'subject'),
                'from' => data_get($mail, 'from'),
                'timestamp' => carbon(data_get($mail, 'timestamp')),
                'is_read' => data_get($mail, 'is_read', false),
            ])
            ->chunk(1000)
            ->each(fn (Collection $chunk) => Mail::upsert($chunk->toArray(), 'id'));

        collect($response)
            ->each(function ($mail) {

                // create recipients
                $recipients = collect(data_get($mail, 'recipients'))
                    ->map(fn ($recipient) => [
                        'mail_id' => data_get($mail, 'mail_id'),
                        'receivable_id' => data_get($recipient, 'recipient_id'),
                        'receivable_type' => $this->getReceivableType(data_get($recipient, 'recipient_type')),
                    ])
                    ->push([
                        'mail_id' => data_get($mail, 'mail_id'),
                        'receivable_id' => data_get($this->getPathValues(), 'character_id'),
                        'receivable_type' => CharacterInfo::class,
                    ])
                    ->unique()
                    ->toArray();

                MailRecipients::upsert($recipients, ['mail_id', 'receivable_id']);

                // Get mail
                $mail_body = Mail::query()->firstWhere('id', data_get($mail, 'mail_id'))?->body;

                // if mail body is not null
                if (! is_null($mail_body)) {
                    // don't dispatch a get mail body job - as it is already present
                    return;
                }

                // Get Mail Body
                MailBodyJob::dispatch($this->job_container, data_get($mail, 'mail_id'))->onQueue($this->queue);
            });

        // see https://divinglaravel.com/avoiding-memory-leaks-when-running-laravel-queue-workers
        // This job is very memory consuming hence avoiding memory leaks, the worker should restart
        app('queue.worker')->shouldQuit = 1;
    }

    private function getReceivableType(string $recipient_type)
    {
        return match ($recipient_type) {
            'alliance' => AllianceInfo::class,
            'character' => CharacterInfo::class,
            'corporation' => CorporationInfo::class,
            'mailing_list' => 'mailing_list'
        };
    }
}
