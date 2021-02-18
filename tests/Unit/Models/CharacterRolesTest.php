<?php


namespace Seatplus\Eveapi\Tests\Unit\Models;


use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Character\CharacterRole;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Tests\TestCase;

class CharacterRolesTest extends TestCase
{
    /** @test */
    public function characterHasCharacterRolesRelationTest()
    {

        $this->assertInstanceOf(CharacterRole::class, $this->test_character->roles);
    }

    /** @test */
    public function characterRoleHasCharacterRelationTest()
    {

        $character_role = $this->test_character->roles;

        $this->assertInstanceOf(CharacterInfo::class, $character_role->character);
    }

    /** @test */
    public function hasRoleTest()
    {

        $character_role = CharacterRole::factory()->make([
            'roles' => ["Contract_Manager"]
        ]);

        $this->assertTrue($character_role->hasRole('roles', 'Contract_Manager'));
    }

    /** @test */
    public function hasDirectorRoleTest()
    {

        $character_role = CharacterRole::factory()->make([
            'roles' => ['Contract_Manager', 'Director']
        ]);

        $this->assertTrue($character_role->hasRole('roles', 'Hangar_Query_3'));
    }

    /** @test */
    public function hasNoMadeUpRole()
    {

        $character_role = CharacterRole::factory()->make([
            'roles' => ['Contract_Manager', 'Director']
        ]);

        $this->assertFalse($character_role->hasRole('roles', 'Something_Made_up'));
    }


}
