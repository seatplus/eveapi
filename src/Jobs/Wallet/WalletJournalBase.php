<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Jobs\Wallet;

use Illuminate\Support\Facades\DB;
use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\EsiResult;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Contracts\Contract;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\Structure;
use Seatplus\Eveapi\Models\Universe\System;
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Models\Wallet\WalletJournal;

abstract class WalletJournalBase extends EsiJob
{
    private array $journalEntries = [];

    abstract protected function fetchPage(EsiClient $esi, int $page): EsiResult;

    abstract protected function walletableId(): int;

    abstract protected function walletableType(): string;

    protected function division(): ?int
    {
        return null;
    }

    #[\Override]
    protected function wrapExecuteJobInTransaction(): bool
    {
        return false;
    }

    #[\Override]
    public function executeJob(EsiClient $esi): void
    {
        $page = 1;
        do {
            $response = $this->fetchPage($esi, $page);
            if ($response->isCachedLoad) {
                return;
            }

            foreach ($response->data as $item) {
                $this->journalEntries[] = [
                    'id' => $item->id,
                    'wallet_journable_id' => $this->walletableId(),
                    'wallet_journable_type' => $this->walletableType(),
                    'division' => $this->division(),
                    'date' => carbon($item->date),
                    'description' => $item->description,
                    'ref_type' => $item->ref_type,
                    'amount' => $item->amount,
                    'balance' => $item->balance,
                    'contextable_id' => $item->context_id,
                    'contextable_type' => $this->getContextableType($item->context_id_type),
                    'first_party_id' => $item->first_party_id,
                    'second_party_id' => $item->second_party_id,
                    'reason' => $item->reason,
                    'tax' => $item->tax,
                    'tax_receiver_id' => $item->tax_receiver_id,
                ];
            }

            $page++;
        } while ($page <= $response->pages);

        // Paging ran outside any transaction; wrap only the final write.
        DB::transaction(fn () => WalletJournal::upsert($this->journalEntries, ['id']));
        if (app()->bound('queue.worker')) {
            app('queue.worker')->shouldQuit = true;
        }
    }

    private function getContextableType(?string $contextIdType): ?string
    {
        if (is_null($contextIdType)) {
            return null;
        }

        return [
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
        ][$contextIdType];
    }
}
