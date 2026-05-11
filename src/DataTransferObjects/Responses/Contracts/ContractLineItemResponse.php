<?php

namespace Seatplus\Eveapi\DataTransferObjects\Responses\Contracts;

readonly class ContractLineItemResponse
{
    public function __construct(
        public int $record_id,
        public bool $is_included,
        public bool $is_singleton,
        public int $quantity,
        public int $type_id,
        public ?int $raw_quantity = null,
    ) {}

    public static function from(object $data): self
    {
        return new self(
            record_id: $data->record_id,
            is_included: $data->is_included,
            is_singleton: $data->is_singleton,
            quantity: $data->quantity,
            type_id: $data->type_id,
            raw_quantity: $data->raw_quantity ?? null,
        );
    }
}
