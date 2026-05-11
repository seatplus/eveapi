<?php

namespace Seatplus\Eveapi\DataTransferObjects\Responses\Assets;

readonly class AssetItemResponse
{
    public function __construct(
        public int $item_id,
        public bool $is_singleton,
        public string $location_flag,
        public int $location_id,
        public string $location_type,
        public int $quantity,
        public int $type_id,
        public ?bool $is_blueprint_copy = null,
    ) {}

    public static function from(object $data): self
    {
        return new self(
            item_id: $data->item_id,
            is_singleton: $data->is_singleton,
            location_flag: $data->location_flag,
            location_id: $data->location_id,
            location_type: $data->location_type,
            quantity: $data->quantity,
            type_id: $data->type_id,
            is_blueprint_copy: $data->is_blueprint_copy ?? null,
        );
    }
}
