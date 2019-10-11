<?php

namespace Seatplus\Eveapi\Actions\Eseye;

class LogWarningsAction
{
    public function execute(EsiResponse $response, int $page = null) : void
    {
        if (! is_null($response->pages) && $page === null) {

            $this->eseye()->getLogger()->warning('Response contained pages but none was expected');
        }

        if (! is_null($page) && $response->pages === null) {

            $this->eseye()->getLogger()->warning('Expected a paged response but had none');
        }

        if (array_key_exists('Warning', $response->headers)) {

            $this->eseye()->getLogger()->warning('A response contained a warning: ' .
                $response->headers['Warning']);
        }
    }
}
