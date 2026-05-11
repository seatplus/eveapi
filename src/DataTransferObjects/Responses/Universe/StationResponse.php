<?php

namespace Seatplus\Eveapi\DataTransferObjects\Responses\Universe;

readonly class StationResponse
{
    public function __construct(
        public int $type_id,
        public string $name,
        public int $system_id,
        public float $reprocessing_efficiency,
        public float $reprocessing_stations_take,
        public float $max_dockable_ship_volume,
        public float $office_rental_cost,
        public ?int $owner = null,
        public ?int $race_id = null,
    ) {}

    public static function from(object $data): self
    {
        return new self(
            type_id: $data->type_id,
            name: $data->name,
            system_id: $data->system_id,
            reprocessing_efficiency: $data->reprocessing_efficiency,
            reprocessing_stations_take: $data->reprocessing_stations_take,
            max_dockable_ship_volume: $data->max_dockable_ship_volume,
            office_rental_cost: $data->office_rental_cost,
            owner: $data->owner ?? null,
            race_id: $data->race_id ?? null,
        );
    }
}
