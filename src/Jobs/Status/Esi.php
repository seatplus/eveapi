<?php

namespace Seatplus\Eveapi\Jobs\Status;

use Seatplus\Eveapi\Jobs\EsiBase;

class Esi extends EsiBase
{
    /**
     * @var string
     */
    protected $method = 'get';

    /**
     * @var string
     */
    protected $endpoint = '/ping';

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     * @throws \Throwable
     */
    public function handle()
    {
        $start = microtime(true);

        try {

            $status = $this->retrieve()->raw;

        } catch (Exception $exception) {

            $status = 'Request failed with: ' . $exception->getMessage();

        }

        $end = microtime(true) - $start;

        logger()->debug('status: ' . $status . ' request_time: ' . $end);
    }
}
