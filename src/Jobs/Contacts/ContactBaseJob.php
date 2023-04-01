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
            Arr::has($object_vars, 'character_id'),
            new Exception('character_id is not set')
        );

        $character_id = $object_vars['character_id'];

        $refresh_token = RefreshToken::find($character_id);

        throw_unless($refresh_token->hasScope($this->getRequiredScope()), new Exception('refresh token does not have required scope'));

        return $refresh_token;
    }
}
