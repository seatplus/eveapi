<?php


namespace Seatplus\Eveapi\Services;


use Seatplus\Eveapi\Models\RefreshToken;

class FindCorporationRefreshToken
{
    private int $corporation_id;

    public function __invoke(int $corporation_id)
    {
        $this->corporation_id = $corporation_id;

        return $this->getDirectorToken() ?? $this->getCharacterToken();
    }

    private function getDirectorToken() : ?RefreshToken
    {
        return RefreshToken::with(['corporation' => fn($query) => $query->where('corporation_id', $this->corporation_id)], 'character.roles')
            ->whereHas('character.roles')
            ->cursor()
            ->shuffle()
            ->first(fn ($token) => $token->character->roles->hasRole('roles','Director'));
    }

    private function getCharacterToken() : ?RefreshToken
    {
        return RefreshToken::with(['corporation' => fn($query) => $query->where('corporation_id', $this->corporation_id)])
            ->cursor()
            ->shuffle()
            ->first();
    }

}
