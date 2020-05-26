<?php


namespace Seatplus\Eveapi\Services;


use Illuminate\Foundation\Bus\DispatchesJobs;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Models\RefreshToken;

class DispatchIndividualUpdate
{
    use DispatchesJobs;

    /**
     * @var \Seatplus\Eveapi\Models\RefreshToken
     */
    private RefreshToken $refresh_token;

    /**
     * @var \Seatplus\Eveapi\Containers\JobContainer
     */
    private JobContainer $job_container;

    public function __construct(RefreshToken $refresh_token)
    {

        $this->refresh_token = $refresh_token;
        $this->job_container = new JobContainer([
            'refresh_token' => $refresh_token
        ]);
    }

    public function execute(string $job_name)
    {
        $job_class = config('eveapi.jobs')[$job_name];

        $job = (new $job_class($this->job_container))->onQueue('high');

        return $this->dispatch($job);
    }

}
