<?php

namespace Seatplus\Eveapi\Actions\Jobs\Alliance;

use Seatplus\Eveapi\Actions\Eseye\RetrieveEsiDataAction;
use Seatplus\Eveapi\Containers\EsiRequestContainer;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;

class AllianceInfoAction
{
    /**
     * @var string
     */
    protected $method = 'get';

    /**
     * @var string
     */
    protected $endpoint = '/alliances/{alliance_id}/';

    /**
     * @var int
     */
    protected $version = 'v3';

    /**
     * @var \Seatplus\Eveapi\Actions\Eseye\RetrieveEsiDataAction
     */
    protected $retrieve_action;

    public function __construct()
    {
        $this->retrieve_action = new RetrieveEsiDataAction();
    }

    public function execute(int $alliance_id)
    {
        $response = $this->retrieve_action->execute(new EsiRequestContainer([
            'method' => $this->method,
            'version' => $this->version,
            'endpoint' => $this->endpoint,
            'path_values' => [
                'alliance_id' => $alliance_id,
            ],
        ]));

        if ($response->isCachedLoad()) return;

        AllianceInfo::firstOrNew(['alliance_id' => $alliance_id])->fill([
            'creator_corporation_id' => $response->creator_corporation_id,
            'creator_id' => $response->creator_id,
            'date_founded' => carbon($response->date_founded),
            'executor_corporation_id' => $response->optional('executor_corporation_id'),
            'faction_id' => $response->optional('faction_id'),
            'name' => $response->name,
            'ticker' => $response->ticker
        ])->save();
    }

}
