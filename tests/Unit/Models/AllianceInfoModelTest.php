<?php

use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Contacts\Label;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\SsoScopes;

it('has morphable sso scope', function () {
    $allianceInfo = AllianceInfo::factory()->create();

    $allianceInfo->ssoScopes()->save(SsoScopes::factory()->make());

    expect($allianceInfo->refresh()->ssoScopes)->toBeInstanceOf(SsoScopes::class);
});

it('has character affiliation', function () {
    $affiliation = $this->test_character->characterAffiliation;

    if (! $affiliation->alliance_id) {
        $alliance = AllianceInfo::factory()->create();
        $affiliation->alliance_id = $alliance->alliance_id;
        $affiliation->save();
    }

    $this->assertNotNull($affiliation->alliance_id);

    $alliance = $affiliation->alliance;

    expect($alliance)->toBeInstanceOf(AllianceInfo::class)
        ->and($alliance->characters->first())->toBeInstanceOf(CharacterInfo::class)
        ->and($alliance->characters->first()->character_id)->toEqual($this->test_character->character_id);

});

it('has corporations relation', function () {
    $characterAffiliation = $this->test_character->characterAffiliation;
    $characterAffiliation->alliance_id = AllianceInfo::factory()->create()->alliance_id;
    $characterAffiliation->save();

    $corporation = $this->test_character->corporation;
    $corporation->alliance_id = $characterAffiliation->alliance_id;
    $corporation->save();

    expect($this->test_character->alliance->corporations->first())->toBeInstanceOf(CorporationInfo::class);
});

it('has many labels', function () {
    $alliance = AllianceInfo::factory()->create();
    $label = Label::factory()->create([
        'labelable_id' => $alliance->alliance_id,
        'labelable_type' => AllianceInfo::class,
    ]);

    expect($alliance->labels)->toHaveCount(1)
        ->and($alliance->labels->first()->is($label))->toBeTrue();
});
