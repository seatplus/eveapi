<?php


namespace Seatplus\Eveapi\Services;


use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Seatplus\Auth\Models\User;
use Seatplus\Eveapi\Models\Character\CharacterInfo;

class GetOwnedIds
{
    /* TODO: This is a duplication from code in seatplus/auth needs refactoring */

    private string $corporation_role;

    public function __construct(string $corporation_role = 'Director')
    {
        $this->corporation_role = $corporation_role;
    }

    public function execute() : array
    {
        return $this->buildOwnedIds()->toArray();
    }

    private function buildOwnedIds(): Collection
    {

        return User::whereId(auth()->user()->getAuthIdentifier())
            ->with('characters.roles', 'characters.corporation')
            ->get()
            ->whenNotEmpty(fn ($collection) => $collection
                ->first()
                ->characters
                // for owned corporation tokens, we need to add the affiliation as long as the character has the required role
                ->map(fn ($character) => [$this->getCorporationId($character), $character->character_id])
                ->flatten()
                ->filter()
            )
            ->flatten()->unique();
    }

    private function getCorporationId(CharacterInfo $character)
    {
        if (! $this->corporation_role || ! $character->roles) {
            return null;
        }

        return $character->roles->hasRole('roles', Str::ucfirst($this->corporation_role)) ? $character->corporation->corporation_id : null;
    }
}
