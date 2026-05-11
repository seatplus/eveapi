<?php

namespace Seatplus\Eveapi\DataTransferObjects\Responses\Universe;

readonly class ConstellationResponse
{
    public function __construct(
        public int $constellation_id,
        public int $region_id,
        public string $name,
    ) {}

    public static function from(object $data): self
    {
        return new self(
            constellation_id: $data->constellation_id,
            region_id: $data->region_id,
            name: $data->name,
        );
    }
}
