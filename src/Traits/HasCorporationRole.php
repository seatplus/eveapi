<?php

namespace Seatplus\Eveapi\Traits;

trait HasCorporationRole
{
    public array $corporation_roles;

    public function getCorporationRoles(): array
    {
        return $this->corporation_roles;
    }

    public function setCorporationRoles(string|array $roles): void
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        $this->corporation_roles = $roles;
    }
}
