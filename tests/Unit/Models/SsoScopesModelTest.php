<?php


use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\SsoScopes;
use Seatplus\Eveapi\Tests\TestCase;

uses(TestCase::class);

test('one can set an alliance relationship from sso scope model', function () {
    $alliance_info = AllianceInfo::factory()->create();

    $alliance_info->ssoScopes()->save(SsoScopes::factory()->make());

    $sso = SsoScopes::factory()->create([
        'morphable_type' => AllianceInfo::class,
        'morphable_id' => $alliance_info->alliance_id,
    ]);

    $this->assertInstanceOf(AllianceInfo::class, $sso->refresh()->morphable);
});

test('has global scope', function () {
    $sso = SsoScopes::updateOrCreate(['type' => 'global'], ['selected_scopes' => collect()->toJson()]);

    $this->assertEquals($sso->type, SsoScopes::global()->first()->type);
});
