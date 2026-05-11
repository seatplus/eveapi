<?php

namespace Seatplus\Eveapi\DataTransferObjects\Responses\Mail;

readonly class MailBodyResponse
{
    public function __construct(
        public string $body,
    ) {}

    public static function from(object $data): self
    {
        return new self(
            body: $data->body,
        );
    }
}
