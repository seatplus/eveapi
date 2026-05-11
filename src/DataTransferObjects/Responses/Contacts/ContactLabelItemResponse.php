<?php

namespace Seatplus\Eveapi\DataTransferObjects\Responses\Contacts;

readonly class ContactLabelItemResponse
{
    public function __construct(
        public int $label_id,
        public string $label_name,
    ) {}

    public static function from(object $data): self
    {
        return new self(
            label_id: $data->label_id,
            label_name: $data->label_name,
        );
    }
}
