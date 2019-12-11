<?php

namespace Seatplus\Eveapi\Actions\Jobs\Corporation;

use Seatplus\Eveapi\Actions\RetrieveFromEsiBase;
use Seatplus\Eveapi\Actions\HasPathValuesInterface;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Alliances\AllianceInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;

class CorporationInfoAction extends RetrieveFromEsiBase implements HasPathValuesInterface
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

    /**
     * @var array|null
     */
    private $path_values;

    public function execute(int $corporation_id)
    {

        $this->setPathValues([
            'corporation_id' => $corporation_id,
        ]);

        $response = $this->retrieve();

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

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getPathValues(): array
    {
        return $this->path_values;
    }

    public function setPathValues(array $path_values): void
    {
        $this->path_values = $path_values;
    }
}
