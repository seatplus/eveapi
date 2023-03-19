<?php

namespace Seatplus\Eveapi\Jobs\Wallet;

use Illuminate\Support\Arr;
use Seatplus\Eveapi\Esi\HasPathValuesInterface;
use Seatplus\Eveapi\Esi\HasRequiredScopeInterface;
use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Contracts\Contract;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\Structure;
use Seatplus\Eveapi\Models\Universe\System;
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Models\Wallet\WalletJournal;
use Seatplus\Eveapi\Traits\HasPages;
use Seatplus\Eveapi\Traits\HasPathValues;
use Seatplus\Eveapi\Traits\HasQueryValues;
use Seatplus\Eveapi\Traits\HasRequiredScopes;

abstract class WalletJournalBase extends EsiBase implements HasPathValuesInterface, HasRequiredScopeInterface
{
    use HasPathValues;
    use HasRequiredScopes;
    use HasPages;
    use HasQueryValues;

    private array $journal_entries = [];

    public function executeJob(): void
    {
        // get path values
        $path_values = $this->getPathValues();

        // get wallet_transactionable_type
        $wallet_journable_type = Arr::has($path_values, 'character_id') ? CharacterInfo::class : CorporationInfo::class;

        $wallet_journable_id = match ($wallet_journable_type) {
            CharacterInfo::class => $path_values['character_id'],
            CorporationInfo::class => $path_values['corporation_id'],
        };

        $division_id = Arr::get($path_values, 'division', null);

        while (true) {
            $response = $this->retrieve($this->getPage());

            if ($response->isCachedLoad()) {
                return;
            }

            $journal_entries = collect($response)
                ->map(fn ($entry) => [
                    'id' => $entry->id,

                    'wallet_journable_id' => $wallet_journable_id,
                    'wallet_journable_type' => $wallet_journable_type,
                    'division' => $division_id,

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
                ])->toArray();

            $this->journal_entries = array_merge($this->journal_entries, $journal_entries);

            // Lastly if more pages are present load next page
            if ($this->getPage() >= $response->pages) {
                break;
            }

            $this->incrementPage();
        }

        WalletJournal::upsert($this->journal_entries, ['id']);

        // see https://divinglaravel.com/avoiding-memory-leaks-when-running-laravel-queue-workers
        // This job is very memory consuming hence avoiding memory leaks, the worker should restart
        app('queue.worker')->shouldQuit = 1;
    }

    private function getContextableType(?string $context_id_type): ?string
    {
        if (is_null($context_id_type)) {
            return null;
        }

        $context_type = [
            'structure_id' => Structure::class,
            'station_id' => Station::class,
            'market_transaction_id' => 'market_transaction_id',
            'character_id' => CharacterInfo::class,
            'corporation_id' => CorporationInfo::class,
            'alliance_id' => AllianceInfo::class,
            'eve_system' => 'eve_system',
            'industry_job_id' => 'industry_job_id',
            'contract_id' => Contract::class,
            'planet_id' => 'planet_id',
            'system_id' => System::class,
            'type_id' => Type::class,
        ];

        return $context_type[$context_id_type];
    }
}
