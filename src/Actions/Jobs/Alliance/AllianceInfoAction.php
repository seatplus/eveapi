<?php

namespace Seatplus\Eveapi\Actions\Jobs\Alliance;

use Seatplus\Eveapi\Actions\Jobs\BaseActionJobAction;
use Seatplus\Eveapi\Actions\Jobs\HasPathValuesInterface;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;

class AllianceInfoAction extends BaseActionJobAction implements HasPathValuesInterface
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
     * @var string
     */
    protected $version = 'v3';

    /**
     * @var array|null
     */
    private $path_values;

    /**
     * @param mixed $alliance_id
     */
    public function setAllianceId(int $alliance_id): void
    {

        $this->alliance_id = $alliance_id;
    }

    public function execute(int $alliance_id)
    {

        $this->setPathValues([
            'alliance_id' => $alliance_id,
        ]);

        $response = $this->retrieve();

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

    public function setPathValues(array $array): void
    {
        $this->path_values = $array;
    }
}
