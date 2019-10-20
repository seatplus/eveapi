<?php


namespace Seatplus\Eveapi\Actions\Eseye;


use Seat\Eseye\Containers\EsiResponse;
use Seat\Eseye\Exceptions\RequestFailedException;
use Seatplus\Eveapi\Containers\EsiRequestContainer;

class RetrieveEsiDataAction
{
    protected $get_eseye_client_action;

    /**
     * @param \Seatplus\Eveapi\Containers\EsiRequestContainer $request
     *
     * @return \Seat\Eseye\Containers\EsiResponse
     * @throws \Seat\Eseye\Exceptions\EsiScopeAccessDeniedException
     * @throws \Seat\Eseye\Exceptions\InvalidAuthenticationException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\RequestFailedException
     * @throws \Seat\Eseye\Exceptions\UriDataMissingException
     */
    public function execute(EsiRequestContainer $request) : EsiResponse
    {

        $this->get_eseye_client_action = new GetEseyeClientAction();

        $client = $this->get_eseye_client_action->execute($request->refresh_token);
        $client->setVersion($request->version);
        $client->setBody($request->request_body);
        $client->setQueryString($request->query_string);

        // Configure the page to get
        if (! is_null($request->page))
            $client->page($request->page);

        try {

            $result = $client->invoke($request->method, $request->endpoint, $request->path_values);
        } catch (RequestFailedException $exception) {

            // If the token can't login and we get an HTTP 400 together with
            // and error message stating that this is an invalid_token, remove
            // the token from SeAT plus.
            if ($exception->getEsiResponse()->getErrorCode() == 400 && in_array($exception->getEsiResponse()->error(), [
                    'invalid_token: The refresh token is expired.',
                    'invalid_token: The refresh token does not match the client specified.',
                ])) {

                // Remove the invalid token
                if( !is_null($request->refresh_token))
                    $request->refresh_token->delete();
            }

            // Rethrow the exception
            throw $exception;
        }

        // If this is a cached load, don't bother with any further
        // processing.
        if ($result->isCachedLoad())
            return $result;

        // Perform error checking
        $this->getLogWarningAction()->setEseyeClient($client)->execute($result, $request->page);

        //TODO update refresh token

        return $result;
    }

    private function getLogWarningAction()
    {

        return new LogWarningsAction();
    }

}
