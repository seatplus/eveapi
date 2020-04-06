<?php


namespace Seatplus\Eveapi\Services\Pipes;


use Seatplus\Eveapi\Jobs\Character\CharacterInfo as CharacterInfoJob;

class CharacterInfo extends Pipe
{

    public function handle($job_container)
    {

        CharacterInfoJob::dispatch($job_container)->onQueue('default');

        $this->next($job_container);

    }
}
