<?php


namespace Seatplus\Eveapi\Actions\Eseye;


use Seat\Eseye\Containers\EsiResponse;
use Seat\Eseye\Exceptions\RequestFailedException;
use Seatplus\Eveapi\Models\RefreshToken;

class RetrieveEsiDataAction
{
    protected $get_eseye_client_action;

    /**
     * @param String                                    $method
     * @param String                                    $version
     * @param String                                    $endpoint
     * @param \Seatplus\Eveapi\Models\RefreshToken|null $refresh_token
     * @param array                                     $path_values
     * @param int|null                                  $page
     *
     * @param array                                     $request_body
     *
     * @param array                                     $query_string
     *
     * @return \Seat\Eseye\Containers\EsiResponse
     * @throws \Seat\Eseye\Exceptions\EsiScopeAccessDeniedException
     * @throws \Seat\Eseye\Exceptions\InvalidAuthenticationException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\RequestFailedException
     * @throws \Seat\Eseye\Exceptions\UriDataMissingException
     */
    public function execute(
        String $method,
        String $version,
        String $endpoint,
        RefreshToken $refresh_token = null,
        array $path_values = [],
        int $page = null,
        array $request_body = [],
        array $query_string = []
    ) : EsiResponse
    {

        $this->get_eseye_client_action = new GetEseyeClientAction();

        $client = $this->get_eseye_client_action->execute($refresh_token);
        $client->setVersion($version);
        $client->setBody($request_body);
        $client->setQueryString($query_string);

        // Configure the page to get
        if (! is_null($page))
            $client->page($page);

        try {

            $result = $client->invoke($method, $endpoint, $path_values);
        } catch (RequestFailedException $exception) {

            // If the token can't login and we get an HTTP 400 together with
            // and error message stating that this is an invalid_token, remove
            // the token from SeAT plus.
            if ($exception->getEsiResponse()->getErrorCode() == 400 && in_array($exception->getEsiResponse()->error(), [
                    'invalid_token: The refresh token is expired.',
                    'invalid_token: The refresh token does not match the client specified.',
                ])) {

                // Remove the invalid token
                if( !is_null($refresh_token))
                    $refresh_token->delete();
            }

            // Rethrow the exception
            throw $exception;
        }

        // If this is a cached load, don't bother with any further
        // processing.
        if ($result->isCachedLoad())
            return $result;

        // Perform error checking
        $this->getLogWarningAction()->execute($result, $page);

        return $result;
    }

    private function getLogWarningAction()
    {

        return new LogWarningsAction();
    }

}