<?php

namespace Seatplus\Eveapi\DataTransferObjects\Responses\Contacts;

readonly class ContactItemResponse
{
    /**
     * @param  array<int>|null  $label_ids
     */
    public function __construct(
        public int $contact_id,
        public string $contact_type,
        public float $standing,
        public ?bool $is_blocked = null,
        public ?bool $is_watched = null,
        public ?array $label_ids = null,
    ) {}

    public static function from(object $data): self
    {
        return new self(
            contact_id: $data->contact_id,
            contact_type: $data->contact_type,
            standing: $data->standing,
            is_blocked: $data->is_blocked ?? null,
            is_watched: $data->is_watched ?? null,
            label_ids: $data->label_ids ?? null,
        );
    }
}
