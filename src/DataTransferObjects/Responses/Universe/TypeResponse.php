<?php

namespace Seatplus\Eveapi\DataTransferObjects\Responses\Universe;

readonly class TypeResponse
{
    public function __construct(
        public int $type_id,
        public int $group_id,
        public string $name,
        public string $description,
        public bool $published,
        public ?float $capacity = null,
        public ?int $graphic_id = null,
        public ?int $icon_id = null,
        public ?int $market_group_id = null,
        public ?float $mass = null,
        public ?float $packaged_volume = null,
        public ?int $portion_size = null,
        public ?float $radius = null,
        public ?float $volume = null,
    ) {}

    public static function from(object $data): self
    {
        return new self(
            type_id: $data->type_id,
            group_id: $data->group_id,
            name: $data->name,
            description: $data->description,
            published: $data->published,
            capacity: $data->capacity ?? null,
            graphic_id: $data->graphic_id ?? null,
            icon_id: $data->icon_id ?? null,
            market_group_id: $data->market_group_id ?? null,
            mass: $data->mass ?? null,
            packaged_volume: $data->packaged_volume ?? null,
            portion_size: $data->portion_size ?? null,
            radius: $data->radius ?? null,
            volume: $data->volume ?? null,
        );
    }
}
