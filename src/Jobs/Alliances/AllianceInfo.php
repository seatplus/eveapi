<?php

namespace Seatplus\Eveapi\Jobs\Alliances;

use Seatplus\Eveapi\Actions\Jobs\Alliance\AllianceInfoAction;
use Seatplus\Eveapi\Jobs\EsiBase;

class AllianceInfo extends EsiBase
{
    /**
     * @var array
     */
    protected $tags = ['alliance', 'info'];

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Seat\Eseye\Exceptions\EsiScopeAccessDeniedException
     * @throws \Seat\Eseye\Exceptions\InvalidAuthenticationException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\RequestFailedException
     * @throws \Seat\Eseye\Exceptions\UriDataMissingException
     */
    public function handle()
    {
        (new AllianceInfoAction())->execute($this->alliance_id);
    }
}
