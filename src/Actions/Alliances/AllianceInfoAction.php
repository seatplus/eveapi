<?php


namespace Seatplus\Eveapi\Actions\Alliances;


use Seatplus\Eveapi\Actions\Eseye\RetrieveEsiDataAction;
use Spatie\QueueableAction\QueueableAction;

class AllianceInfoAction
{
    use QueueableAction;

    protected $retrieve_esi_data_action;

    private function getMethod() :string
    {
        return 'get';
    }

    private function getEndpoint() :string
    {
        return '/alliances/{alliance_id}/';
    }

    private function getVersion() :string
    {
        return 'v3';
    }

    /**
     * @param int $alliance_id
     * @return \Seat\Eseye\Containers\EsiResponse
     * @throws \Seat\Eseye\Exceptions\EsiScopeAccessDeniedException
     * @throws \Seat\Eseye\Exceptions\InvalidAuthenticationException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\RequestFailedException
     * @throws \Seat\Eseye\Exceptions\UriDataMissingException
     */
    public function execute(int $alliance_id)
    {
        $this->retrieve_esi_data_action = new RetrieveEsiDataAction();

        $alliance_info = $this
            ->retrieve_esi_data_action
            ->execute(
                $this->getMethod(),
                $this->getVersion(),
                $this->getEndpoint(),
                null,
                ['alliance_id' => $alliance_id]
            );

        return $alliance_info;

    }

}