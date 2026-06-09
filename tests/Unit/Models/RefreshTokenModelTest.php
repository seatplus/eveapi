<?php

use Carbon\Carbon;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\RefreshToken;

it('has character relationship', function () {
    expect($this->test_character->refreshToken->character)->toBeInstanceOf(CharacterInfo::class);
});

it('has corporation relationship', function () {
    expect($this->test_character->refreshToken->corporation)->toBeInstanceOf(CorporationInfo::class);
});

it('only returns token if it is not already considered expired', function () {
    $refreshToken = RefreshToken::factory()->make();

    expect($refreshToken)
        ->expires_on->timestamp->toBeGreaterThan(Illuminate\Support\Carbon::now()->timestamp)
        ->token->toBeString();

    $refreshToken->expires_on = Carbon::now()->subMinutes(2);

    expect($refreshToken)
        ->expires_on->timestamp->toBeLessThan(Illuminate\Support\Carbon::now()->timestamp)
        ->token->toBeNull();
});
