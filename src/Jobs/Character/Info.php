<?php

namespace Seatplus\Eveapi\Jobs\Character;

use Seatplus\Eveapi\Actions\Jobs\Character\InfoAction;
use Seatplus\Eveapi\Jobs\EsiBase;

class Info extends EsiBase
{

    /**
     * @var array
     */
    protected $tags = ['character', 'info'];

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {

        (new InfoAction())->execute($this->character_id);

        return;

    }
}
