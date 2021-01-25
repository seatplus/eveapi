<?php


namespace Seatplus\Eveapi\Services\Wallet;


use Seat\Eseye\Containers\EsiResponse;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\Structure;
use Seatplus\Eveapi\Models\Universe\System;
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Models\Wallet\WalletJournal;

class ProcessWalletJournalResponse
{

    public function __construct(
        private int $wallet_journable_id,
        private string $wallet_journable_type
    )
    {
    }

    public function execute(EsiResponse $response)
    {
        return collect($response)
            ->each(fn($entry) => WalletJournal::firstOrCreate(
                [
                    'id' => $entry->id]
                ,
                [
                    'wallet_journable_id' => $this->wallet_journable_id,
                    'wallet_journable_type' => $this->wallet_journable_type,
                    // required props
                    'date' => carbon($entry->date),
                    'description' => $entry->description,
                    'ref_type' => $entry->ref_type,
                    // nullable props
                    'amount' => optional($entry)->amount,
                    'balance' => optional($entry)->balance,
                    'contextable_id' => optional($entry)->context_id,
                    'contextable_type' => $this->getContextableType(optional($entry)->context_id_type),
                    'first_party_id' => optional($entry)->first_party_id,
                    'second_party_id' => optional($entry)->second_party_id,
                    'reason' => optional($entry)->reason,
                    'tax' => optional($entry)->tax,
                    'tax_receiver_id' => optional($entry)->tax_receiver_id,
                ]
            ));
    }

    private function getContextableType(?string $context_id_type) : ?string
    {
        if(is_null($context_id_type))
            return null;

        $context_type = [
            'structure_id' => Structure::class,
            'station_id' => Station::class,
            'market_transaction_id' => 'market_transaction_id',
            'character_id' => CharacterInfo::class,
            'corporation_id' => CorporationInfo::class,
            'alliance_id' => AllianceInfo::class,
            'eve_system' => 'eve_system',
            'industry_job_id' => 'industry_job_id',
            'contract_id' => 'contract_id',
            'planet_id' => 'planet_id',
            'system_id' => System::class,
            'type_id' => Type::class
        ];

        return $context_type[$context_id_type];
    }
}
