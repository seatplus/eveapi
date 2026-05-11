<?php

namespace Seatplus\Eveapi\DataTransferObjects\Responses\Contracts;

readonly class ContractItemResponse
{
    public function __construct(
        public int $contract_id,
        public int $acceptor_id,
        public int $assignee_id,
        public string $availability,
        public string $date_expired,
        public string $date_issued,
        public bool $for_corporation,
        public int $issuer_corporation_id,
        public int $issuer_id,
        public string $status,
        public string $type,
        public ?float $buyout = null,
        public ?float $collateral = null,
        public ?string $date_accepted = null,
        public ?string $date_completed = null,
        public ?int $days_to_complete = null,
        public ?float $price = null,
        public ?float $reward = null,
        public ?int $end_location_id = null,
        public ?int $start_location_id = null,
        public ?string $title = null,
        public ?float $volume = null,
    ) {}

    public static function from(object $data): self
    {
        return new self(
            contract_id: $data->contract_id,
            acceptor_id: $data->acceptor_id,
            assignee_id: $data->assignee_id,
            availability: $data->availability,
            date_expired: $data->date_expired,
            date_issued: $data->date_issued,
            for_corporation: $data->for_corporation,
            issuer_corporation_id: $data->issuer_corporation_id,
            issuer_id: $data->issuer_id,
            status: $data->status,
            type: $data->type,
            buyout: $data->buyout ?? null,
            collateral: $data->collateral ?? null,
            date_accepted: $data->date_accepted ?? null,
            date_completed: $data->date_completed ?? null,
            days_to_complete: $data->days_to_complete ?? null,
            price: $data->price ?? null,
            reward: $data->reward ?? null,
            end_location_id: $data->end_location_id ?? null,
            start_location_id: $data->start_location_id ?? null,
            title: $data->title ?? null,
            volume: $data->volume ?? null,
        );
    }
}
