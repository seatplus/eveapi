<?php

namespace Seatplus\Eveapi\Actions\Jobs\Character;

use Seatplus\Eveapi\Models\Character\CharacterRole;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Traits\RetrieveEsiResponse;

class CharacterRoleAction
{
    use RetrieveEsiResponse;
    /**
     * @var string
     */
    protected $method = 'get';

    /**
     * @var string
     */
    protected $endpoint = '/characters/{character_id}/roles/';

    /**
     * @var int
     */
    protected $version = 'v2';

    protected $refresh_token;

    public $required_scope = 'esi-characters.read_corporation_roles.v1';

    public function execute(RefreshToken $refresh_token)
    {

        $this->refresh_token = $refresh_token;

        $response = $this->retrieve([
            'character_id' => $refresh_token->character_id,
        ]);

        if ($response->isCachedLoad()) return;

        CharacterRole::updateOrCreate([
            'character_id' => $refresh_token->character_id,
        ], [
            'roles' => $response->roles,
            'roles_at_base' => $response->roles_at_base,
            'roles_at_hq' => $response->roles_at_hq,
            'roles_at_other' => $response->roles_at_other,
        ]);

    }
}
