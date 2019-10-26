<?php

namespace Seatplus\Eveapi\Actions\Jobs\Character;


use Seatplus\Eveapi\Actions\Eseye\RetrieveEsiDataAction;
use Seatplus\Eveapi\Actions\Jobs\Alliance\AllianceInfoAction;
use Seatplus\Eveapi\Containers\EsiRequestContainer;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Alliances\AllianceInfo;
use Seatplus\Eveapi\Models\Character\CharacterInfo;

class CharacterInfoAction
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


        if (! empty($response->optional('alliance_id')))
        {
            $job_container = new JobContainer([
                'alliance_id' => $response->alliance_id
            ]);

            AllianceInfo::dispatch($job_container)->onQueue('low');
        }

    }
}
