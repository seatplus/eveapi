<?php


namespace Seatplus\Eveapi\Actions\Jobs;


use Exception;
use Seat\Eseye\Containers\EsiResponse;
use Seatplus\Eveapi\Actions\Eseye\RetrieveEsiDataAction;
use Seatplus\Eveapi\Containers\EsiRequestContainer;

abstract class BaseJobAction implements BaseJobInterface
{

    /**
     * @var \Seatplus\Eveapi\Actions\Eseye\RetrieveEsiDataAction
     */
    private $retrieve_action;

    /**
     * @var \Seatplus\Eveapi\Containers\EsiRequestContainer
     */
    private $esi_request_container;

    public function __construct()
    {
        $this->retrieve_action = new RetrieveEsiDataAction();
        $this->esi_request_container = $this->getBaseEsiReuestContainer();

    }

    /**
     * @return \Seat\Eseye\Containers\EsiResponse
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Seat\Eseye\Exceptions\EsiScopeAccessDeniedException
     * @throws \Seat\Eseye\Exceptions\InvalidAuthenticationException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\RequestFailedException
     * @throws \Seat\Eseye\Exceptions\UriDataMissingException
     */
    public function retrieve() : EsiResponse
    {
        if(! $this->hasRequiredScope() && isset($this->required_scope))
            throw new Exception('refresh_token misses required scope: ' . $this->required_scope);

        return $this->retrieve_action->execute($this->esi_request_container);
    }

    private function getBaseEsiReuestContainer() : EsiRequestContainer
    {
        return new EsiRequestContainer([
            'method' => $this->getMethod(),
            'version' => $this->getVersion(),
            'endpoint' => $this->getEndpoint(),
        ]);
    }

    public function setPathValues(array $path_vaules)
    {
        $this->esi_request_container->path_values = $path_vaules;
    }

    public function setRequestBody(array $request_body) :void
    {
        $this->esi_request_container->request_body = $request_body;
    }

    private function hasRequiredScope() : bool
    {
        if(isset($this->required_scope))
            if (isset($this->refresh_token)) {
                return $this->refresh_token->hasScope($this->required_scope);
            }

        return true;
    }

}
