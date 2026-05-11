<?php

namespace Seatplus\Eveapi\DataTransferObjects\Responses\Universe;

readonly class SystemResponse
{
    public function __construct(
        public int $system_id,
        public int $constellation_id,
        public string $name,
        public float $security_status,
        public ?string $security_class = null,
    ) {}

    public static function from(object $data): self
    {
        return new self(
            system_id: $data->system_id,
            constellation_id: $data->constellation_id,
            name: $data->name,
            security_status: $data->security_status,
            security_class: $data->security_class ?? null,
        );
    }
}
