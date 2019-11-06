<?php

namespace Seatplus\Eveapi\Actions\Esi;

use Exception;
use Seatplus\Eveapi\Actions\Eseye\RetrieveEsiDataAction;
use Seatplus\Eveapi\Containers\EsiRequestContainer;
use Seatplus\Eveapi\Traits\RateLimitsEsiCalls;

class GetEsiStatusAction
{
    use RateLimitsEsiCalls;

    /**
     * @var string
     */
    protected $method = 'get';

    /**
     * @var string
     */
    protected $endpoint = '/ping';

    protected $retrieve_action;

    public function __construct()
    {
        $this->retrieve_action = new RetrieveEsiDataAction();
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     * @throws \Throwable
     */
    public function execute() : string
    {
        if($this->isEsiRateLimited())
            return 'rate limited';

        // If recently a status request was made, return the cached result
        if(! is_null($cache = cache('esi-status')))
            return $cache['status'];

        $start = microtime(true);

        try {

            $status = $this->retrieve_action->execute(new EsiRequestContainer([
                'method' => $this->method,
                'endpoint' => $this->endpoint,
            ]))->raw;

        } catch (Exception $exception) {

            $status = 'Request failed with: ' . $exception->getMessage();

        }

        $end = microtime(true) - $start;

        cache(['esi-status' => [
            'status' => $status,
            'request_time' => $end,
        ]], now()->addSeconds(10));

        return $status;

    }
}
