<?php

namespace Seatplus\Eveapi\Jobs\Mail;

use Illuminate\Support\Collection;
use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\Resources\Mail\GetCharactersCharacterIdMail;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Mail\Mail;
use Seatplus\Eveapi\Models\Mail\MailRecipients;
use Seatplus\Eveapi\Models\RefreshToken;

final class MailHeaderJob extends EsiJob
{
    protected const string OPERATION_CLASS = GetCharactersCharacterIdMail::class;

    public function __construct(public int $character_id) {}

    #[\Override]
    public function getRefreshToken(): RefreshToken
    {
        return RefreshToken::findOrFail($this->character_id);
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
    protected function executeJob(EsiClient $esi): void
    {
        $response = self::OPERATION_CLASS::execute($esi, $this->character_id);
        if ($response->isCachedLoad) {
            return;
        }

        collect($response->data)
            ->map(fn (object $item) => [
                'id' => $item->mail_id,
                'subject' => $item->subject,
                'from' => $item->from,
                'timestamp' => carbon($item->timestamp),
                'is_read' => $item->is_read ?? null,
                'recipients' => $item->recipients ?? [],
            ])
            ->tap(function (Collection $mails) {
                $mails->map(function (array $mail) {
                    unset($mail['recipients']);

                    return $mail;
                })
                    ->chunk(1000)
                    ->each(fn (Collection $chunk) => Mail::upsert($chunk->toArray(), 'id'));
            })
            ->tap(fn (Collection $mails) => $this->handleRecipients($mails))
            ->tap(fn (Collection $mails) => $this->handleMailBody($mails));

        if (app()->bound('queue.worker')) {
            app('queue.worker')->shouldQuit = true;
        }
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
            ->filter(fn (array $mail) => ! in_array(data_get($mail, 'id'), $existing_recipients))
            ->map(fn (array $mail) => collect(data_get($mail, 'recipients'))
                ->map(fn (object $recipient) => [
                    'mail_id' => data_get($mail, 'id'),
                    'receivable_id' => data_get($recipient, 'recipient_id'),
                    'receivable_type' => $this->getReceivableType(data_get($recipient, 'recipient_type')),
                ])
                ->push([
                    'mail_id' => data_get($mail, 'id'),
                    'receivable_id' => $this->character_id,
                    'receivable_type' => CharacterInfo::class,
                ])
                ->unique()
                ->toArray())
            ->flatten(1)
            ->toArray();

        MailRecipients::upsert($recipients, ['mail_id', 'receivable_id']);
    }

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
