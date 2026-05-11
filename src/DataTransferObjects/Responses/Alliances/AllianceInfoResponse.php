<?php

namespace Seatplus\Eveapi\DataTransferObjects\Responses\Alliances;

readonly class AllianceInfoResponse
{
    public function __construct(
        public int $creator_corporation_id,
        public int $creator_id,
        public string $date_founded,
        public string $name,
        public string $ticker,
        public ?int $executor_corporation_id = null,
        public ?int $faction_id = null,
    ) {}

    public static function from(object $data): self
    {
        return new self(
            creator_corporation_id: $data->creator_corporation_id,
            creator_id: $data->creator_id,
            date_founded: $data->date_founded,
            name: $data->name,
            ticker: $data->ticker,
            executor_corporation_id: $data->executor_corporation_id ?? null,
            faction_id: $data->faction_id ?? null,
        );
    }
}
