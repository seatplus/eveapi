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
