<?php

namespace Seatplus\Eveapi\DataTransferObjects\Responses\Assets;

readonly class AssetNameItemResponse
{
    public function __construct(
        public int $item_id,
        public string $name,
    ) {}

    public static function from(object $data): self
    {
        return new self(
            item_id: $data->item_id,
            name: $data->name,
        );
    }
}
