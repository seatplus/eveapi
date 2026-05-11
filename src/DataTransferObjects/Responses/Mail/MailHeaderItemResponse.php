<?php

namespace Seatplus\Eveapi\DataTransferObjects\Responses\Mail;

readonly class MailHeaderItemResponse
{
    /**
     * @param  array<object>|null  $recipients
     */
    public function __construct(
        public int $mail_id,
        public string $subject,
        public int $from,
        public string $timestamp,
        public bool $is_read = false,
        public ?array $recipients = null,
    ) {}

    public static function from(object $data): self
    {
        return new self(
            mail_id: $data->mail_id,
            subject: $data->subject,
            from: $data->from,
            timestamp: $data->timestamp,
            is_read: $data->is_read ?? false,
            recipients: $data->recipients ?? null,
        );
    }
}
