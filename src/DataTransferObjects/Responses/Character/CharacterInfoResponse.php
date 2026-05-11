<?php

namespace Seatplus\Eveapi\DataTransferObjects\Responses\Character;

readonly class CharacterInfoResponse
{
    public function __construct(
        public string $name,
        public string $birthday,
        public string $gender,
        public int $race_id,
        public int $bloodline_id,
        public ?float $security_status = null,
        public ?int $faction_id = null,
        public ?string $description = null,
        public ?string $title = null,
    ) {}

    public static function from(object $data): self
    {
        return new self(
            name: $data->name,
            birthday: $data->birthday,
            gender: $data->gender,
            race_id: $data->race_id,
            bloodline_id: $data->bloodline_id,
            security_status: $data->security_status ?? null,
            faction_id: $data->faction_id ?? null,
            description: $data->description ?? null,
            title: $data->title ?? null,
        );
    }
}
