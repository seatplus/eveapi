<?php

namespace Seatplus\Eveapi\DataTransferObjects\Responses\Corporation;

readonly class CorporationDivisionsResponse
{
    /**
     * @param  array<DivisionEntryResponse>  $hangar
     * @param  array<DivisionEntryResponse>  $wallet
     */
    public function __construct(
        public array $hangar,
        public array $wallet,
    ) {}

    public static function from(object $data): self
    {
        return new self(
            hangar: array_map(
                fn (object $entry) => DivisionEntryResponse::from($entry),
                (array) ($data->hangar ?? [])
            ),
            wallet: array_map(
                fn (object $entry) => DivisionEntryResponse::from($entry),
                (array) ($data->wallet ?? [])
            ),
        );
    }
}
