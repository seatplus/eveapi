<?php

use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingBodysFromMails;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingCategorys;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingCharacterInfosFromCorporationMemberTracking;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingConstellations;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingGroups;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingLocationFromAssets;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingLocationFromContracts;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingLocationFromCorporationMemberTracking;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingLocationFromWalletTransaction;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingLocations;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingRegions;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingTypesFromCharacterAssets;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingTypesFromContractItem;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingTypesFromCorporationMemberTracking;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingTypesFromLocations;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingTypesFromSkillQueue;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingTypesFromSkills;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingTypesFromWalletTransaction;
use Seatplus\Eveapi\Models\Mail\Mail;

it('returns early if batch is cancelled', function (string $job) {
    $job = mock($job)->makePartial();

    $job->shouldReceive('batch->cancelled')->andReturn(true);

    $job->handle();

    $job->shouldNotHaveReceived('batch->add');
})->with([
    GetMissingBodysFromMails::class,
    GetMissingCategorys::class,
    GetMissingCharacterInfosFromCorporationMemberTracking::class,
    GetMissingConstellations::class,
    GetMissingGroups::class,
    GetMissingLocationFromAssets::class,
    GetMissingLocationFromContracts::class,
    GetMissingLocationFromCorporationMemberTracking::class,
    GetMissingLocationFromWalletTransaction::class,
    GetMissingLocations::class,
    GetMissingRegions::class,
    GetMissingTypesFromCharacterAssets::class,
    GetMissingTypesFromContractItem::class,
    GetMissingTypesFromCorporationMemberTracking::class,
    GetMissingTypesFromLocations::class,
    GetMissingTypesFromSkillQueue::class,
    GetMissingTypesFromSkills::class,
    GetMissingTypesFromWalletTransaction::class,
]);

it('returns null if no refresh token is found', function () {

    Event::fakeFor(function () {
        Mail::factory()->create();
    });

    $job = mock(GetMissingBodysFromMails::class)->makePartial();

    $job->shouldReceive('batch->cancelled')->andReturn(false);
    $job->shouldReceive('batch->add')->once()->with([]);

    $job->handle();
});
