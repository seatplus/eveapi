<?php

namespace Seatplus\Eveapi\Actions\Jobs\Corporation;

use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Alliances\AllianceInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Traits\RetrieveEsiResponse;

class CorporationInfoAction
{
    use RetrieveEsiResponse;

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

    public function execute(int $corporation_id)
    {

        $response = $this->retrieve([
            'corporation_id' => $corporation_id,
        ]);

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
