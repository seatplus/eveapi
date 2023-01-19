<?php

namespace Seatplus\Eveapi\Services\Esi;

use Composer\InstalledVersions;
use Seatplus\EsiClient\CacheMiddleware\LaravelFileCacheMiddleware;
use Seatplus\EsiClient\Configuration;
use Seatplus\EsiClient\DataTransferObjects\EsiAuthentication;
use Seatplus\EsiClient\DataTransferObjects\EsiConfiguration;
use Seatplus\EsiClient\EsiClient;

class EsiClientSetup
{
    public function __construct()
    {
        $config = Configuration::getInstance();

        $esi_configuration = new EsiConfiguration(
            http_user_agent: 'SeAT plus v' . InstalledVersions::getPrettyVersion('seatplus/eveapi'),
            // ESI
            datasource: config('eveapi.config.esi-client.datasource'),
            esi_scheme: config('eveapi.config.esi-client.esi_scheme'),
            esi_host: config('eveapi.config.esi-client.esi_host'),
            esi_port: config('eveapi.config.esi-client.esi_port'),
            // SSO
            sso_scheme: config('eveapi.config.esi-client.sso_scheme'),
            sso_host: config('eveapi.config.esi-client.sso_host'),
            sso_port: config('eveapi.config.esi-client.sso_port'),
            // Logging
            logger_level: config('eveapi.config.esi-client.logger_level'),
            logfile_location: config('eveapi.config.esi-client.logfile_location'),
            // Cache
            cache_middleware: LaravelFileCacheMiddleware::class,
        );

        $config->setConfiguration($esi_configuration);
    }

    public function get(?EsiAuthentication $authentication = null): EsiClient
    {
        $client = new EsiClient;

        if ($authentication) {
            tap($authentication, function ($auth) {
                $auth->client_id = config('eveapi.config.esi.eve_client_id');
                $auth->secret = config('eveapi.config.esi.eve_client_secret');
            });

            $client->setAuthentication($authentication);
        }

        return $client;
    }
}
