<?php

namespace Seatplus\Eveapi\DataTransferObjects\Responses\Corporation;

readonly class CorporationWalletItemResponse
{
    public function __construct(
        public int $division,
        public float $balance,
    ) {}

    public static function from(object $data): self
    {
        return new self(
            division: $data->division,
            balance: $data->balance,
        );
    }
}
