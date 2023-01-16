<?php

namespace Seatplus\Eveapi\Esi;

interface HasCorporationRoleInterface
{

    public function getCorporationRole(): string;

    public function setCorporationRole(string $role): void;

}