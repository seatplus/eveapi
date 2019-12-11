<?php

namespace Seatplus\Eveapi\Actions;

use Seat\Eseye\Containers\EsiResponse;
use Seatplus\Eveapi\Actions\Eseye\RetrieveEsiDataAction;
use Seatplus\Eveapi\Actions\RetrieveFromEsiInterface;
use Seatplus\Eveapi\Containers\EsiRequestContainer;

abstract class RetrieveFromEsiBase implements RetrieveFromEsiInterface
{

    /**
     * @var \Seatplus\Eveapi\Containers\EsiRequestContainer|null
     */
    private $esi_request_container;

    /**
     * @param int|null $page
     *
     * @return \Seat\Eseye\Containers\EsiResponse
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Seat\Eseye\Exceptions\EsiScopeAccessDeniedException
     * @throws \Seat\Eseye\Exceptions\InvalidAuthenticationException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\RequestFailedException
     * @throws \Seat\Eseye\Exceptions\UriDataMissingException
     */
    public function retrieve(?int $page = null) : EsiResponse
    {
        $this->builldEsiRequestContainer($page);

        return (new RetrieveEsiDataAction)->execute($this->esi_request_container);
    }

    private function getBaseEsiReuestContainer() : EsiRequestContainer
    {
        return new EsiRequestContainer([
            'method' => $this->getMethod(),
            'version' => $this->getVersion(),
            'endpoint' => $this->getEndpoint(),
        ]);
    }

    private function builldEsiRequestContainer(?int $page)
    {
        $this->esi_request_container = $this->getBaseEsiReuestContainer();

        if(method_exists($this, 'getPathValues'))
            $this->esi_request_container->path_values = $this->getPathValues();

        if(method_exists($this, 'getRequestBody'))
            $this->esi_request_container->request_body = $this->getRequestBody();

        if(method_exists($this, 'getRefreshToken'))
            $this->esi_request_container->refresh_token = $this->getRefreshToken();

        $this->esi_request_container->page = $page;
    }
}
