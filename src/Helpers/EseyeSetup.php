<?php

namespace Seatplus\Eveapi\Helpers;

use Seat\Eseye\Configuration;
use Seat\Eseye\Containers\EsiAuthentication;
use Seat\Eseye\Eseye;

class EseyeSetup
{
    /**
     * EseyeSetup constructor.
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    public function __construct()
    {

        $config = Configuration::getInstance();
        $config->http_user_agent = 'SeAT plus v' . config('eveapi.config.version');
        $config->logfile_location = config('eveapi.config.eseye_logfile');
        $config->file_cache_location = config('eveapi.config.eseye_cache');
        $config->logger_level = config('eveapi.config.eseye_loglevel');
        $config->esi_scheme = env('EVE_ESI_SCHEME', 'https');
        $config->esi_host = env('EVE_ESI_HOST', 'esi.evetech.net');
        $config->esi_port = env('EVE_ESI_PORT', 443);
        $config->datasource = env('EVE_ESI_DATASOURCE', 'tranquility');
        $config->sso_scheme = env('EVE_SSO_SCHEME', 'https');
        $config->sso_host = env('EVE_SSO_HOST', 'login.eveonline.com');
        $config->sso_port = env('EVE_SSO_PORT', 443);
    }

    /**
     * Gets an instance of Eseye.
     *
     * We automatically add the CLIENT_ID and SHARED_SECRET configured
     * for this SeAT plus instance to the EsiAuthentication container.
     *
     * @param \Seat\Eseye\Containers\EsiAuthentication $authentication
     *
     * @return \Seat\Eseye\Eseye
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    public function get(EsiAuthentication $authentication = null): Eseye
    {

        if ($authentication) {

            tap($authentication, function ($auth) {

                $auth->client_id = env('EVE_CLIENT_ID');
                $auth->secret = env('EVE_CLIENT_SECRET');
            });

            return new Eseye($authentication);
        }

        // Return an unauthenticated Eseye instance
        return new Eseye;
    }
}
