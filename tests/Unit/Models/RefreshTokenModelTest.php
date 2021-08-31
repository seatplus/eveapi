<?php


use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Tests\TestCase;

uses(TestCase::class);

it('has character relationship', function () {
    $this->assertInstanceOf(CharacterInfo::class, $this->test_character->refresh_token->character);
});

it('has corporation relationship', function () {
    $this->assertInstanceOf(CorporationInfo::class, $this->test_character->refresh_token->corporation);
});
