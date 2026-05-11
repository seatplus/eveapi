<?php

namespace Seatplus\Eveapi\DataTransferObjects\Responses\Killmails;

readonly class KillmailAttackerResponse
{
    public function __construct(
        public int $damage_done,
        public bool $final_blow,
        public ?int $character_id = null,
        public ?int $corporation_id = null,
        public ?int $alliance_id = null,
        public ?int $ship_type_id = null,
        public ?int $weapon_type_id = null,
        public ?float $security_status = null,
        public ?int $faction_id = null,
    ) {}

    public static function from(object $data): self
    {
        return new self(
            damage_done: $data->damage_done,
            final_blow: $data->final_blow,
            character_id: $data->character_id ?? null,
            corporation_id: $data->corporation_id ?? null,
            alliance_id: $data->alliance_id ?? null,
            ship_type_id: $data->ship_type_id ?? null,
            weapon_type_id: $data->weapon_type_id ?? null,
            security_status: $data->security_status ?? null,
            faction_id: $data->faction_id ?? null,
        );
    }
}
