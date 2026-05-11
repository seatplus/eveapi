<?php

namespace Seatplus\Eveapi\DataTransferObjects\Responses\Universe;

readonly class StructureResponse
{
    public function __construct(
        public string $name,
        public int $owner_id,
        public int $solar_system_id,
        public ?int $type_id = null,
    ) {}

    public static function from(object $data): self
    {
        return new self(
            name: $data->name,
            owner_id: $data->owner_id,
            solar_system_id: $data->solar_system_id,
            type_id: $data->type_id ?? null,
        );
    }
}
