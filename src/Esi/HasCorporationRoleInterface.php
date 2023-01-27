<?php

namespace Seatplus\Eveapi\Esi;

interface HasCorporationRoleInterface
{
    public function getCorporationRoles(): array;

    public function setCorporationRoles(string|array $roles): void;
}
