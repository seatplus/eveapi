<?php

namespace Seatplus\Eveapi\DataTransferObjects\Responses\Corporation;

readonly class CorporationMemberTrackingItemResponse
{
    public function __construct(
        public int $character_id,
        public ?string $start_date = null,
        public ?int $base_id = null,
        public ?string $logon_date = null,
        public ?string $logoff_date = null,
        public ?int $location_id = null,
        public ?int $ship_type_id = null,
    ) {}

    public static function from(object $data): self
    {
        return new self(
            character_id: $data->character_id,
            start_date: $data->start_date ?? null,
            base_id: $data->base_id ?? null,
            logon_date: $data->logon_date ?? null,
            logoff_date: $data->logoff_date ?? null,
            location_id: $data->location_id ?? null,
            ship_type_id: $data->ship_type_id ?? null,
        );
    }
}
