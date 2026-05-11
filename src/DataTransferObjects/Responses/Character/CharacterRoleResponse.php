<?php

namespace Seatplus\Eveapi\DataTransferObjects\Responses\Character;

readonly class CharacterRoleResponse
{
    /**
     * @param  array<string>  $roles
     * @param  array<string>  $roles_at_base
     * @param  array<string>  $roles_at_hq
     * @param  array<string>  $roles_at_other
     */
    public function __construct(
        public array $roles,
        public array $roles_at_base,
        public array $roles_at_hq,
        public array $roles_at_other,
    ) {}

    public static function from(object $data): self
    {
        return new self(
            roles: $data->roles ?? [],
            roles_at_base: $data->roles_at_base ?? [],
            roles_at_hq: $data->roles_at_hq ?? [],
            roles_at_other: $data->roles_at_other ?? [],
        );
    }
}
