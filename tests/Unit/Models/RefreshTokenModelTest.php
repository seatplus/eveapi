<?php


use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Tests\TestCase;

uses(TestCase::class);

it('has character relationship', function () {
    expect($this->test_character->refresh_token->character)->toBeInstanceOf(CharacterInfo::class);
});

it('has corporation relationship', function () {
    expect($this->test_character->refresh_token->corporation)->toBeInstanceOf(CorporationInfo::class);
});

it('only returns token if it is not already considered expired', function () {
    $refresh_token = \Seatplus\Eveapi\Models\RefreshToken::factory()->make();

    expect($refresh_token)
        ->expires_on->toBeGreaterThan(\Illuminate\Support\Carbon::now())
        ->token->toBeString();

    $refresh_token->expires_on = \Carbon\Carbon::now()->subMinutes(2);

    expect($refresh_token)
        ->expires_on->toBeLessThan(\Illuminate\Support\Carbon::now())
        ->token->toBeNull();
});
