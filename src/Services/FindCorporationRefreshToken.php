<?php


namespace Seatplus\Eveapi\Services;


use Seatplus\Eveapi\Models\RefreshToken;

class FindCorporationRefreshToken
{

    public function __invoke(int $corporation_id, string $scope, string $role)
    {
        return RefreshToken::with(['corporation' => fn($query) => $query->where('corporation_id', $corporation_id)], 'character.roles')
            ->whereHas('character.roles')
            ->cursor()
            ->shuffle()
            ->filter(fn($token) => in_array($scope, $token->scopes))
            ->first(fn ($token) => $token->character->roles->hasRole('roles', $role));
    }

}
