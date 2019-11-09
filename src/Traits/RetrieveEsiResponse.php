<?php


namespace Seatplus\Eveapi\Traits;


use Exception;
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

        if(! $this->hasRequiredScope())
            throw new Exception('refresh_token misses required scope. Scope is: ' . $this->scope);

        $path_values = $path_values ?? [];

        return $this->retrieve_action->execute(new EsiRequestContainer([
            'method' => $this->method,
            'version' => $this->version,
            'endpoint' => $this->endpoint,
            'path_values' => $path_values,
            'refresh_token' => property_exists($this, 'refresh_token') ? $this->refresh_token : []
        ]));
    }

    private function hasRequiredScope() : bool
    {
        if(property_exists($this, 'required_scope'))
            return $this->refresh_token->hasScope($this->required_scope);

        return true;

    }


}
