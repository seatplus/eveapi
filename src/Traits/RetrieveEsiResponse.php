<?php


namespace Seatplus\Eveapi\Traits;


use Seatplus\Eveapi\Actions\Eseye\RetrieveEsiDataAction;
use Seatplus\Eveapi\Containers\EsiRequestContainer;

trait RetrieveEsiResponse
{
    /**
     * @var \Seatplus\Eveapi\Actions\Eseye\RetrieveEsiDataAction
     */
    protected $retrieve_action;

    public function __construct()
    {
        $this->retrieve_action = new RetrieveEsiDataAction();
    }

    public function retrieve(?array $path_values = [])
    {

        $path_values = $path_values ?? [];

        return $this->retrieve_action->execute(new EsiRequestContainer([
            'method' => $this->method,
            'version' => $this->version,
            'endpoint' => $this->endpoint,
            'path_values' => $path_values
        ]));
    }


}
