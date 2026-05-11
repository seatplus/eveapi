<?php

namespace Seatplus\Eveapi\DataTransferObjects\Responses\Corporation;

readonly class DivisionEntryResponse
{
    public function __construct(
        public int $division,
        public ?string $name = null,
    ) {}

    public static function from(object $data): self
    {
        return new self(
            division: $data->division,
            name: $data->name ?? null,
        );
    }
}
