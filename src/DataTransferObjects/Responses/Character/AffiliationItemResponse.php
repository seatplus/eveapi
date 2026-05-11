<?php

namespace Seatplus\Eveapi\DataTransferObjects\Responses\Character;

readonly class AffiliationItemResponse
{
    public function __construct(
        public int $character_id,
        public int $corporation_id,
        public ?int $alliance_id = null,
        public ?int $faction_id = null,
    ) {}

    public static function from(object $data): self
    {
        return new self(
            character_id: $data->character_id,
            corporation_id: $data->corporation_id,
            alliance_id: $data->alliance_id ?? null,
            faction_id: $data->faction_id ?? null,
        );
    }
}
