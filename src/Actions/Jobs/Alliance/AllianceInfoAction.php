<?php

namespace Seatplus\Eveapi\Actions\Jobs\Alliance;

use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Traits\RetrieveEsiResponse;

class AllianceInfoAction
{

    //TODO write unit test
    use RetrieveEsiResponse;

    /**
     * @var string
     */
    protected $method = 'get';

    /**
     * @var string
     */
    protected $endpoint = '/alliances/{alliance_id}/';

    /**
     * @var string
     */
    protected $version = 'v3';

    public function execute(int $alliance_id)
    {

        $response = $this->retrieve([
                'alliance_id' => $alliance_id,
            ]);

        if ($response->isCachedLoad()) return;

        AllianceInfo::firstOrNew(['alliance_id' => $alliance_id])->fill([
            'creator_corporation_id' => $response->creator_corporation_id,
            'creator_id' => $response->creator_id,
            'date_founded' => carbon($response->date_founded),
            'executor_corporation_id' => $response->optional('executor_corporation_id'),
            'faction_id' => $response->optional('faction_id'),
            'name' => $response->name,
            'ticker' => $response->ticker,
        ])->save();
    }
}
