<?php

namespace Seatplus\Eveapi\DataTransferObjects\Responses\Corporation;

readonly class CorporationInfoResponse
{
    public function __construct(
        public string $ticker,
        public string $name,
        public int $member_count,
        public int $ceo_id,
        public int $creator_id,
        public float $tax_rate,
        public ?int $alliance_id = null,
        public ?string $date_founded = null,
        public ?string $description = null,
        public ?int $faction_id = null,
        public ?int $home_station_id = null,
        public ?int $shares = null,
        public ?string $url = null,
        public ?bool $war_eligible = null,
    ) {}

    public static function from(object $data): self
    {
        return new self(
            ticker: $data->ticker,
            name: $data->name,
            member_count: $data->member_count,
            ceo_id: $data->ceo_id,
            creator_id: $data->creator_id,
            tax_rate: $data->tax_rate,
            alliance_id: $data->alliance_id ?? null,
            date_founded: $data->date_founded ?? null,
            description: $data->description ?? null,
            faction_id: $data->faction_id ?? null,
            home_station_id: $data->home_station_id ?? null,
            shares: $data->shares ?? null,
            url: $data->url ?? null,
            war_eligible: $data->war_eligible ?? null,
        );
    }
}
