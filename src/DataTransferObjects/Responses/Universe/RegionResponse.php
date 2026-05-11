<?php

namespace Seatplus\Eveapi\DataTransferObjects\Responses\Universe;

readonly class RegionResponse
{
    public function __construct(
        public int $region_id,
        public string $name,
        public ?string $description = null,
    ) {}

    public static function from(object $data): self
    {
        return new self(
            region_id: $data->region_id,
            name: $data->name,
            description: $data->description ?? null,
        );
    }
}
