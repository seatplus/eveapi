<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Character\CharacterRole;
use Seatplus\Eveapi\Models\Contracts\Contract;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking;
use Seatplus\Eveapi\Models\LocationRefreshToken;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Wallet\WalletTransaction;
use Seatplus\Eveapi\Services\ResolveLocation\Finders\FinderInterface;
use Seatplus\Eveapi\Services\ResolveLocation\Finders\ThroughCharacterAssetsFinder;
use Seatplus\Eveapi\Services\ResolveLocation\Finders\ThroughContractsFinder;
use Seatplus\Eveapi\Services\ResolveLocation\Finders\ThroughCorporationMemberTrackingFinder;
use Seatplus\Eveapi\Services\ResolveLocation\Finders\ThroughPreviouslyFailedRefreshTokenFinder;
use Seatplus\Eveapi\Services\ResolveLocation\Finders\ThroughRandomRefreshTokenFinder;
use Seatplus\Eveapi\Services\ResolveLocation\Finders\ThroughSuccessfulRefreshTokenFinder;
use Seatplus\Eveapi\Services\ResolveLocation\Finders\ThroughWalletTransactionsFinder;
use Seatplus\Eveapi\Services\ResolveLocation\Resolver\StructureRefreshTokenFinder;

beforeEach(function () {
    Queue::fake();
    Event::fake();

    $refresh_token = updateRefreshTokenScopes($this->test_character->refresh_token, ['esi-universe.read_structures.v1']);
    $refresh_token->save();

    CharacterRole::factory()->create([
        'character_id' => test()->test_character->character_id,
        'roles' => ['Director'],
    ]);

    $this->character_id = test()->test_character->character_id;
    $this->corporation_id = $this->test_character->corporation->corporation_id;
    $this->location_id = 60_005_617;
});

describe('filters through different finders', function () {

    it('finds token ThroughSuccessfulRefreshTokenFinder', function (){
        LocationRefreshToken::query()
            ->updateOrCreate([
                'location_id' => test()->location_id,
                'character_id' => test()->character_id,
            ], [
                'resolved' => true,
            ]);

        $instance = new ThroughSuccessfulRefreshTokenFinder();

        executeFindStructureRefreshTokenTest($instance);

    });

    it('finds token ThroughCharacterAssetsFinder', function (){
        Asset::factory()->create([
            'location_id' => test()->location_id,
            'assetable_id' => test()->character_id,
            'assetable_type' => CharacterInfo::class,
        ]);

        $instance = new ThroughCharacterAssetsFinder();

        executeFindStructureRefreshTokenTest($instance);
    });

    it('finds token ThroughContractsFinder', function (){
        Contract::factory()->create([
            'start_location_id' => test()->location_id,
            'issuer_id' => test()->character_id,
        ]);

        $instance = new ThroughContractsFinder();

        executeFindStructureRefreshTokenTest($instance);
    });

    it('finds token ThroughCorporationMemberTrackingFinder via character', function (){
        CorporationMemberTracking::factory()->create([
            'location_id' => test()->location_id,
            'character_id' => test()->character_id,
        ]);

        $instance = new ThroughCorporationMemberTrackingFinder();

        executeFindStructureRefreshTokenTest($instance);
    });

    it('finds token ThroughCorporationMemberTrackingFinder via director', function (){
        CorporationMemberTracking::factory()->create([
            'location_id' => test()->location_id,
            'character_id' => test()->character_id++, // just to make sure it is different
            'corporation_id' => test()->corporation_id,
        ]);

        $instance = new ThroughCorporationMemberTrackingFinder();

        executeFindStructureRefreshTokenTest($instance);
    });

    it('finds token ThroughPreviouslyFailedRefreshTokenFinder', function (){
        LocationRefreshToken::query()
            ->create([
                'location_id' => test()->location_id,
                'character_id' => test()->character_id,
                'resolved' => false,
                'attempts' => 1,
            ]);

        $instance = new ThroughPreviouslyFailedRefreshTokenFinder();

        executeFindStructureRefreshTokenTest($instance);
    });

    it('finds token ThroughRandomRefreshTokenFinder', function (){
        $instance = new ThroughRandomRefreshTokenFinder();

        executeFindStructureRefreshTokenTest($instance);
    });

    it('finds token ThroughWalletTransactionsFinder via character', function (){
        WalletTransaction::factory()->create([
            'location_id' => test()->location_id,
            'wallet_transactionable_id' => test()->character_id,
            'wallet_transactionable_type' => CharacterInfo::class,
        ]);

        $instance = new ThroughWalletTransactionsFinder();

        executeFindStructureRefreshTokenTest($instance);
    });

    it('finds token ThroughWalletTransactionsFinder via director', function (){
        WalletTransaction::factory()->create([
            'location_id' => test()->location_id,
            'wallet_transactionable_id' => test()->corporation_id,
            'wallet_transactionable_type' => CorporationInfo::class,
        ]);

        $instance = new ThroughWalletTransactionsFinder();

        executeFindStructureRefreshTokenTest($instance);
    });

});

function executeFindStructureRefreshTokenTest(FinderInterface $instance)
{
    $tracings = LocationRefreshToken::query()
        ->where('location_id', test()->location_id)
        ->inRandomOrder()
        ->get();

    // Act
    $result = $instance->handle(test()->location_id, $tracings);

    // Assert
    expect($result)->toBeInstanceOf(RefreshToken::class);
}

it('allows shortcut findValidToken method', function () {
    $instance = new StructureRefreshTokenFinder(testCharacter()->refresh_token);

    $result = $instance->findValidToken(test()->location_id);


    expect($result)->toBeInstanceOf(RefreshToken::class)
        ->character_id->toBe(testCharacter()->character_id);
});

it('increments and resets attempts', function () {
    $refresh_token = test()->test_character->refresh_token;
    $location_id = test()->location_id;

    $instance = new StructureRefreshTokenFinder($refresh_token);

    $instance->findValidToken($location_id);

    $instance->markAsFailed();

    expect(LocationRefreshToken::all())
        ->toHaveCount(1)
        ->first()->attempts->toBe(1)
        ->first()->resolved->toBeFalsy();

    $instance->markAsResolved();

    $location_refresh_token = LocationRefreshToken::query()
        ->where('location_id', test()->location_id)
        ->where('character_id', test()->test_character->character_id)
        ->first();

    expect(LocationRefreshToken::all())
        ->toHaveCount(1)
        ->first()->attempts->toBe(0)
        ->first()->resolved->toBeTruthy();

});

it('goes through all finder classes', function () {
    // Arrange
    LocationRefreshToken::query()
        ->create([
            'location_id' => test()->location_id,
            'character_id' => test()->character_id,
            'resolved' => false,
            'attempts' => 1,
        ]);

    $instance = new StructureRefreshTokenFinder();

    // make sure the ThrougRandomRefreshTokenFinder did not result positively
    $random_token = (new ThroughRandomRefreshTokenFinder)->handle(test()->location_id, LocationRefreshToken::all());

    // Act
    $result = $instance->findValidToken(test()->location_id);

    // Assert
    expect($random_token)->toBeNull()
        ->and($result)->toBeInstanceOf(RefreshToken::class);
});
