<?php

namespace Seatplus\Eveapi\Actions\Jobs\Character;

use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Alliances\AllianceInfo;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Character\CharacterRoles;
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

    protected $required_scope = 'esi-characters.read_corporation_roles.v1';

    public function execute(RefreshToken $refresh_token)
    {

        $this->refresh_token = $refresh_token;

        $response = $this->retrieve([
            'character_id' => $refresh_token->character_id,
        ]);

        if ($response->isCachedLoad()) return;

        CharacterRoles::updateOrCreate([
            'character_id' => $refresh_token->character_id
        ], [
            'roles' => json_encode($response->roles),
            'roles_at_base' => json_encode($response->roles_at_base),
            'roles_at_hq' => json_encode($response->roles_at_hq),
            'roles_at_other' => json_encode($response->roles_at_other)
        ]);

    }
}
