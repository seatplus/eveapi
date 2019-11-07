<?php

namespace Seatplus\Eveapi\Actions\Jobs\Corporation;

use Seatplus\Eveapi\Actions\Eseye\RetrieveEsiDataAction;
use Seatplus\Eveapi\Containers\EsiRequestContainer;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Alliances\AllianceInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;

class CorporationInfoAction
{
    /**
     * @var string
     */
    protected $method = 'get';

    /**
     * @var string
     */
    protected $endpoint = '/corporations/{corporation_id}/';

    /**
     * @var int
     */
    protected $version = 'v4';

    protected $retrieve_action;

    public function __construct()
    {

        $this->retrieve_action = new RetrieveEsiDataAction();
    }

    public function execute(int $corporation_id)
    {

        $response = $this->retrieve_action->execute(new EsiRequestContainer([
            'method'      => $this->method,
            'version'     => $this->version,
            'endpoint'    => $this->endpoint,
            'path_values' => [
                'corporation_id' => $corporation_id,
            ],
        ]));

        if ($response->isCachedLoad()) return;

        CorporationInfo::firstOrNew(['corporation_id' => $corporation_id])->fill([
            'ticker'          => $response->ticker,
            'name'            => $response->name,
            'member_count'    => $response->member_count,
            'ceo_id'          => $response->ceo_id,
            'creator_id'      => $response->creator_id,
            'tax_rate'        => $response->tax_rate,
            'alliance_id'     => $response->optional('alliance_id'),
            'date_founded'    => $response->optional('date_founded'),
            'description'     => $response->optional('description'),
            'faction_id'      => $response->optional('faction_id'),
            'home_station_id' => $response->optional('home_station_id'),
            'shares'          => $response->optional('shares'),
            'url'             => $response->optional('url'),
            'war_eligible'    => $response->optional('war_eligible'),
        ])->save();

        if (! empty($response->optional('alliance_id'))) {
            $job_container = new JobContainer([
                'alliance_id' => $response->alliance_id,
            ]);

            AllianceInfo::dispatch($job_container)->onQueue('low');
        }

    }
}
