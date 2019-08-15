<?php


namespace Seatplus\Eveapi\Jobs\Alliances;


use Seatplus\Eveapi\Actions\Alliances\AllianceInfoAction;
use Seatplus\Eveapi\Jobs\EsiBase;

class AllianceInfo extends EsiBase
{

    /**
     * @var \Seatplus\Eveapi\Actions\Alliances\AllianceInfoAction
     */
    private $alliance_info_action;

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
        $this->alliance_info_action = new AllianceInfoAction();
        $this->alliance_info_action->execute($this->getAllianceId());
    }
}