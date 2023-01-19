<?php

namespace Seatplus\Eveapi\Traits;

trait HasCorporationRole
{
    public string $corporation_role;

    public function getCorporationRole(): string
    {
        return $this->corporation_role;
    }

    public function setCorporationRole(string $role): void
    {
        $this->corporation_role = $role;
    }
}
