<?php

namespace Seatplus\Eveapi\Actions\Jobs\Character;

use Seatplus\Eveapi\Actions\Alliances\AllianceInfoAction;
use Seatplus\Eveapi\Actions\Eseye\RetrieveEsiDataAction;
use Seatplus\Eveapi\Containers\EsiRequestContainer;
use Seatplus\Eveapi\Models\Character\CharacterInfo;

class InfoAction
{
    /**
     * @var string
     */
    protected $method = 'get';

    /**
     * @var string
     */
    protected $endpoint = '/characters/{character_id}/';

    /**
     * @var int
     */
    protected $version = 'v4';

    protected $retrieve_action;

    public function __construct()
    {
        $this->retrieve_action = new RetrieveEsiDataAction();
    }

    public function execute(int $character_id)
    {
        $response = $this->retrieve_action->execute(new EsiRequestContainer([
            'method' => $this->method,
            'version' => $this->version,
            'endpoint' => $this->endpoint,
            'path_values' => [
                'character_id' => $character_id,
            ],
        ]));

        if ($response->isCachedLoad()) return;

        CharacterInfo::firstOrNew(['character_id' => $character_id])->fill([
            'name'            => $response->name,
            'description'     => $response->optional('description'),
            'corporation_id'  => $response->corporation_id,
            'alliance_id'     => $response->optional('alliance_id'),
            'birthday'        => $response->birthday,
            'gender'          => $response->gender,
            'race_id'         => $response->race_id,
            'bloodline_id'    => $response->bloodline_id,
            'ancestry_id'    => $response->optional('ancestry_id'),
            'security_status' => $response->optional('security_status'),
            'faction_id'      => $response->optional('faction_id'),
        ])->save();

        /*if (! empty($response->optional('alliance_id')))
        {
            $alliance_info_action = new AllianceInfoAction();
            $alliance_info_action->onQueue()->execute($response->alliance_id);
        }*/

    }
}
