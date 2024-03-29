<?php

/*
 * MIT License
 *
 * Copyright (c) 2019, 2020, 2021 Felix Huber
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Seatplus\Eveapi\Jobs\Seatplus;

use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingBodysFromMails;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingCategorys;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingCharacterInfosFromCorporationMemberTracking;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingConstellations;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingGroups;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingLocationFromAssets;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingLocationFromContracts;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingLocationFromCorporationMemberTracking;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingLocationFromWalletTransaction;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingRegions;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingTypesFromCharacterAssets;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingTypesFromContractItem;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingTypesFromCorporationMemberTracking;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingTypesFromLocations;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingTypesFromSkillQueue;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingTypesFromSkills;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingTypesFromWalletTransaction;
use Seatplus\Eveapi\Models\BatchStatistic;

class MaintenanceJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function tags(): array
    {
        return [
            'Maintenance',
        ];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $batch = $this->dispatchBatch();

        BatchStatistic::createEntry($batch);
    }

    private function dispatchBatch(): Batch
    {
        return Bus::batch([

            new GetMissingGroups,
            new GetMissingCategorys,
            new GetMissingCharacterInfosFromCorporationMemberTracking,

            // Constellations and Regions
            new GetMissingConstellations,
            new GetMissingRegions,

            // Locations
            new GetMissingLocationFromWalletTransaction,
            new GetMissingLocationFromCorporationMemberTracking,
            new GetMissingLocationFromAssets,
            new GetMissingLocationFromContracts,

            // TODO: Missing character_info from character_users
            // TODO: Missing Affiliations from character_users, character_info and contacts
            // TODO: Update CorporationInfo and AllianceInfo

            // Types
            new GetMissingTypesFromContractItem,
            new GetMissingTypesFromCorporationMemberTracking,
            new GetMissingTypesFromWalletTransaction,
            new GetMissingTypesFromCharacterAssets,
            new GetMissingTypesFromLocations,
            new GetMissingTypesFromSkills,
            new GetMissingTypesFromSkillQueue,

            // Mails
            new GetMissingBodysFromMails,

        ])
            ->then(fn (Batch $batch) => BatchStatistic::where('batch_id', $batch->id)->update(['finished_at' => now()]))
            ->name('Maintenance Job')
            ->allowFailures()
            ->dispatch();
    }
}
