<?php

namespace Seatplus\Eveapi\DataTransferObjects\Responses\Character;

readonly class CorporationHistoryItemResponse
{
    public function __construct(
        public int $record_id,
        public int $corporation_id,
        public ?bool $is_deleted = null,
        public ?string $start_date = null,
    ) {}

    public static function from(object $data): self
    {
        return new self(
            record_id: $data->record_id,
            corporation_id: $data->corporation_id,
            is_deleted: $data->is_deleted ?? null,
            start_date: $data->start_date ?? null,
        );
    }
}
