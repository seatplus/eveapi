<?php

namespace Seatplus\Eveapi\Jobs\Character;

use Seatplus\Eveapi\Actions\Jobs\Character\CharacterInfoAction;
use Seatplus\Eveapi\Jobs\EsiBase;

class CharacterInfo extends EsiBase
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

        (new CharacterInfoAction())->execute($this->character_id);

    }
}
