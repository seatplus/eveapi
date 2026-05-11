<?php

namespace Seatplus\Eveapi\DataTransferObjects\Responses\Wallet;

readonly class WalletTransactionItemResponse
{
    public function __construct(
        public int $transaction_id,
        public int $client_id,
        public string $date,
        public bool $is_buy,
        public bool $is_personal,
        public int $journal_ref_id,
        public int $location_id,
        public int $quantity,
        public int $type_id,
        public float $unit_price,
    ) {}

    public static function from(object $data): self
    {
        return new self(
            transaction_id: $data->transaction_id,
            client_id: $data->client_id,
            date: $data->date,
            is_buy: $data->is_buy,
            is_personal: $data->is_personal,
            journal_ref_id: $data->journal_ref_id,
            location_id: $data->location_id,
            quantity: $data->quantity,
            type_id: $data->type_id,
            unit_price: $data->unit_price,
        );
    }
}
