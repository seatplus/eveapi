<?php

namespace Seatplus\Eveapi\DataTransferObjects\Responses\Wallet;

readonly class WalletJournalItemResponse
{
    public function __construct(
        public int $id,
        public string $date,
        public string $description,
        public string $ref_type,
        public ?float $amount = null,
        public ?float $balance = null,
        public ?int $context_id = null,
        public ?string $context_id_type = null,
        public ?int $first_party_id = null,
        public ?int $second_party_id = null,
        public ?string $reason = null,
        public ?float $tax = null,
        public ?int $tax_receiver_id = null,
    ) {}

    public static function from(object $data): self
    {
        return new self(
            id: $data->id,
            date: $data->date,
            description: $data->description,
            ref_type: $data->ref_type,
            amount: $data->amount ?? null,
            balance: $data->balance ?? null,
            context_id: $data->context_id ?? null,
            context_id_type: $data->context_id_type ?? null,
            first_party_id: $data->first_party_id ?? null,
            second_party_id: $data->second_party_id ?? null,
            reason: $data->reason ?? null,
            tax: $data->tax ?? null,
            tax_receiver_id: $data->tax_receiver_id ?? null,
        );
    }
}
