<?php

namespace Seatplus\Eveapi\Actions\Esi;

use Exception;
use Seatplus\Eveapi\Actions\Jobs\RetrieveFromEsiBase;
use Seatplus\Eveapi\Traits\RateLimitsEsiCalls;

class GetEsiStatusAction extends RetrieveFromEsiBase
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

    /**
     * @var string
     */
    protected $version = '';

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     * @throws \Throwable
     * @throws \Psr\SimpleCache\InvalidArgumentException
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

            $status = $this->retrieve()->raw;

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
}
