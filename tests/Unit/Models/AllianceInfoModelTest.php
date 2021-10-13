<?php


use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\SsoScopes;

it('has morphable sso scope', function () {
    $alliance_info = AllianceInfo::factory()->create();

    $alliance_info->ssoScopes()->save(SsoScopes::factory()->make());

    expect($alliance_info->refresh()->ssoScopes)->toBeInstanceOf(SsoScopes::class);
});

it('has character affiliation', function () {
    $affiliation = $this->test_character->character_affiliation;

    if (! $affiliation->alliance_id) {
        $alliance = AllianceInfo::factory()->create();
        $affiliation->alliance_id = $alliance->alliance_id;
        $affiliation->save();
    }

    $this->assertNotNull($affiliation->alliance_id);

    $alliance = $affiliation->alliance;

    expect($alliance)->toBeInstanceOf(AllianceInfo::class);

    expect($alliance->characters->first())->toBeInstanceOf(CharacterInfo::class);

    expect($alliance->characters->first()->character_id)->toEqual($this->test_character->character_id);
});

it('has corporations relation', function () {
    $character_affiliation = $this->test_character->character_affiliation;
    $character_affiliation->alliance_id = AllianceInfo::factory()->create()->alliance_id;
    $character_affiliation->save();

    $corporation = $this->test_character->corporation;
    $corporation->alliance_id = $character_affiliation->alliance_id;
    $corporation->save();

    expect($this->test_character->alliance->corporations->first())->toBeInstanceOf(CorporationInfo::class);
});
