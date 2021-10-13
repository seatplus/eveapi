<?php


use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\SsoScopes;
use Seatplus\Eveapi\Tests\TestCase;



test('one can set an alliance relationship from sso scope model', function () {
    $alliance_info = AllianceInfo::factory()->create();

    $alliance_info->ssoScopes()->save(SsoScopes::factory()->make());

    $sso = SsoScopes::factory()->create([
        'morphable_type' => AllianceInfo::class,
        'morphable_id' => $alliance_info->alliance_id,
    ]);

    expect($sso->refresh()->morphable)->toBeInstanceOf(AllianceInfo::class);
});

test('has global scope', function () {
    $sso = SsoScopes::updateOrCreate(['type' => 'global'], ['selected_scopes' => collect()->toJson()]);

    expect(SsoScopes::global()->first()->type)->toEqual($sso->type);
});
