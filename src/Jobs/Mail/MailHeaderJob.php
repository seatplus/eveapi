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

use Illuminate\Support\Collection;
use Seatplus\Eveapi\Esi\HasPathValuesInterface;
use Seatplus\Eveapi\Esi\HasRequiredScopeInterface;
use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Jobs\Middleware\HasRequiredScopeMiddleware;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Mail\Mail;
use Seatplus\Eveapi\Models\Mail\MailRecipients;
use Seatplus\Eveapi\Traits\HasPathValues;
use Seatplus\Eveapi\Traits\HasRequiredScopes;

class MailHeaderJob extends EsiBase implements HasPathValuesInterface, HasRequiredScopeInterface
{
    use HasPathValues;
    use HasRequiredScopes;

    public function __construct(public int $character_id)
    {
        parent::__construct(
            method: 'get',
            endpoint: '/characters/{character_id}/mail/',
            version: 'v1',
        );

        $this->setRequiredScope('esi-mail.read_mail.v1');

        $this->setPathValues([
            'character_id' => $this->character_id,
        ]);
    }

    #[\Override]
    public function tags(): array
    {
        return [
            'mail',
            'header',
            "character_id:{$this->character_id}",
        ];
    }

    #[\Override]
    public function middleware(): array
    {
        return [
            new HasRequiredScopeMiddleware,
            ...parent::middleware(),
        ];
    }

    #[\Override]
    public function executeJob(): void
    {
        $response = $this->retrieve();

        if ($response->isCachedLoad()) {
            return;
        }

        collect($response->data)
            ->map(fn (object $mail) => [
                'id' => data_get($mail, 'mail_id'),
                'subject' => data_get($mail, 'subject'),
                'from' => data_get($mail, 'from'),
                'timestamp' => carbon(data_get($mail, 'timestamp')),
                'is_read' => data_get($mail, 'is_read', false),
                'recipients' => data_get($mail, 'recipients'),
            ])
            // create the mail header
            ->tap(function (Collection $mails) {

                $mails->map(function (array $mail) {
                    unset($mail['recipients']); // remove recipients from mail header

                    return $mail; // return mail header
                })
                    ->chunk(1000)
                    ->each(fn (Collection $chunk) => Mail::upsert($chunk->toArray(), 'id'));
            })
            // handle recipients
            ->tap(fn (Collection $mails) => $this->handleRecipients($mails))
            // handle mail body
            ->tap(fn (Collection $mails) => $this->handleMailBody($mails));

        // see https://divinglaravel.com/avoiding-memory-leaks-when-running-laravel-queue-workers
        // This job is very memory consuming hence avoiding memory leaks, the worker should restart
        app('queue.worker')->shouldQuit = true;
    }

    private function getReceivableType(string $recipient_type): string
    {
        return match ($recipient_type) {
            'alliance' => AllianceInfo::class,
            'character' => CharacterInfo::class,
            'corporation' => CorporationInfo::class,
            'mailing_list' => 'mailing_list',
            default => throw new \Exception("Unknown recipient type {$recipient_type}"),
        };
    }

    public function handleRecipients(Collection $mails): void
    {

        $existing_recipients = MailRecipients::query()
            ->whereIn('mail_id', $mails->pluck('id'))
            ->get('mail_id')
            ->toArray();

        $recipients = $mails
            // filter out mails that already have recipients recorded
            ->filter(fn (array $mail) => ! in_array(data_get($mail, 'id'), $existing_recipients))
            ->map(function (array $mail) {
                // create recipients array for mail
                return collect(data_get($mail, 'recipients'))
                    ->map(fn (object $recipient) => [
                        'mail_id' => data_get($mail, 'id'),
                        'receivable_id' => data_get($recipient, 'recipient_id'),
                        'receivable_type' => $this->getReceivableType(data_get($recipient, 'recipient_type')),
                    ])
                    ->push([
                        'mail_id' => data_get($mail, 'id'),
                        'receivable_id' => data_get($this->getPathValues(), 'character_id'),
                        'receivable_type' => CharacterInfo::class,
                    ])
                    ->unique()
                    ->toArray();
            })
            // flatten the collection to a single array
            ->flatten(1)
            ->toArray();

        MailRecipients::upsert($recipients, ['mail_id', 'receivable_id']);
    }

    /**
     * @param  Collection<object>  $mails
     */
    public function handleMailBody(Collection $mails): void
    {
        Mail::query()
            ->whereIn('id', $mails->pluck('id'))
            ->whereNull('body')
            ->select('id')
            ->get()
            ->each(fn (Mail $mail) => $this->batching()
                ? $this->batch()->add([new MailBodyJob($this->character_id, $mail->id)])
                : MailBodyJob::dispatch($this->character_id, $mail->id)->onQueue($this->queue)
            );
    }
}
