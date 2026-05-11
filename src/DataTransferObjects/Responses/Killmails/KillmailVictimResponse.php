<?php

namespace Seatplus\Eveapi\DataTransferObjects\Responses\Killmails;

readonly class KillmailVictimResponse
{
    public function __construct(
        public int $ship_type_id,
        public ?int $damage_taken = null,
        public ?int $character_id = null,
        public ?int $corporation_id = null,
        public ?int $alliance_id = null,
        public ?int $faction_id = null,
    ) {}

    public static function from(object $data): self
    {
        return new self(
            ship_type_id: $data->ship_type_id,
            damage_taken: $data->damage_taken ?? null,
            character_id: $data->character_id ?? null,
            corporation_id: $data->corporation_id ?? null,
            alliance_id: $data->alliance_id ?? null,
            faction_id: $data->faction_id ?? null,
        );
    }
}
