<?php


namespace Seatplus\Eveapi\Jobs\Hydrate\Maintenance;


use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Models\Wallet\WalletTransaction;

class GetMissingTypesFromWalletTransaction extends HydrateMaintenanceBase
{

    public function handle()
    {
        if ($this->batch()->cancelled()) {
            // Determine if the batch has been cancelled...

            return;
        }

        $type_ids = WalletTransaction::doesntHave('type')->pluck('type_id')->unique()->values();

        $type_ids->each(fn($id) => ResolveUniverseTypeByIdJob::dispatch($id)->onQueue('high'));
    }
}