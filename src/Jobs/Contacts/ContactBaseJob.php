<?php

namespace Seatplus\Eveapi\Jobs\Contacts;

use Exception;
use Illuminate\Support\Arr;
use Seatplus\Eveapi\Esi\HasPathValuesInterface;
use Seatplus\Eveapi\Esi\HasRequiredScopeInterface;
use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Traits\HasPathValues;

abstract class ContactBaseJob extends EsiBase implements HasPathValuesInterface, HasRequiredScopeInterface
{
    use HasPathValues;

    protected string $required_scope;

    public function setRequiredScope(string $required_scope): void
    {
        $this->required_scope = $required_scope;
    }

    public function getRequiredScope(): string
    {
        return $this->required_scope;
    }

    public function getRefreshToken(): RefreshToken
    {
        // throw exception if required scope is not set
        throw_unless($this->getRequiredScope(), new Exception('required scope is not set'));

        $object_vars = get_object_vars($this);

        // throw exception if character_id, corporation_id or alliance_id is not set
        throw_unless(
            Arr::hasAny($object_vars, ['character_id', 'corporation_id', 'alliance_id']),
            new Exception('character_id, corporation_id or alliance_id is not set')
        );

        // if corporation_id is set, get refresh token for corporation_id
        if (Arr::has($object_vars, 'corporation_id')) {
            return $this->getToken('corporation_id', $object_vars['corporation_id']);
        }

        // if alliance_id is set, get refresh token for alliance_id
        if (Arr::has($object_vars, 'alliance_id')) {
            return $this->getToken('alliance_id', $object_vars['alliance_id']);
        }

        // if neither corporation_id nor alliance_id is set, get refresh token for character_id
        return $this->getToken('character_id', $object_vars['character_id']);
    }

    private function getToken(string $key, int $value): RefreshToken
    {
        return RefreshToken::query()
            ->when($key === 'character_id', fn ($query) => $query->where('character_id', $value))
            ->when($key === 'corporation_id', fn ($query) => $query->whereRelation('corporation', 'corporation_infos.corporation_id', $value))
            ->when($key === 'alliance_id', fn ($query) => $query->whereRelation('corporation.alliance', 'alliance_infos.alliance_id', $value))
            ->get()
            ->first(fn ($refresh_token) => $refresh_token->hasScope($this->getRequiredScope()));
    }
}
