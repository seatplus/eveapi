<?php

namespace Seatplus\Eveapi\DataTransferObjects\Responses\Wallet;

readonly class CharacterBalanceResponse
{
    public function __construct(
        public float $balance,
    ) {}

    /**
     * ESI returns a raw float for this endpoint.
     * PHP's `(object) float` creates a stdClass with a `scalar` property.
     */
    public static function from(object $data): self
    {
        return new self(balance: $data->scalar);
    }
}
