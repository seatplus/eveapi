<?php

/*
 * MIT License
 *
 * Copyright (c) 2019, 2020, 2021 Felix Huber
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Seatplus\Eveapi\Helpers;

use Seat\Eseye\Cache\FileCache;
use Seat\Eseye\Cache\RedisCache;
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

        $config->cache = FileCache::class; //RedisCache::class;
        /*$config->redis_cache_location = env('REDIS_HOST', '127.0.0.1');
        $config->logfile_location = config('eveapi.config.eseye_logfile');
        $config->logger_level = config('eveapi.config.eseye_loglevel');*/

        $config->logfile_location = config('eveapi.config.eseye_logfile');
        $config->file_cache_location = config('eveapi.config.eseye_cache');
        $config->logger_level = config('eveapi.config.eseye_loglevel');

        /*$config->esi_scheme = env('EVE_ESI_SCHEME', 'https');
        $config->esi_host = env('EVE_ESI_HOST', 'esi.evetech.net');
        $config->esi_port = env('EVE_ESI_PORT', 443);
        $config->datasource = env('EVE_ESI_DATASOURCE', 'tranquility');
        $config->sso_scheme = env('EVE_SSO_SCHEME', 'https');
        $config->sso_host = env('EVE_SSO_HOST', 'login.eveonline.com');
        $config->sso_port = env('EVE_SSO_PORT', 443);*/

        $config->esi_scheme = config('eveapi.config.eseye_esi_scheme');
        $config->esi_host = config('eveapi.config.eseye_esi_host');
        $config->esi_port = config('eveapi.config.eseye_esi_port');
        $config->datasource = config('eveapi.config.eseye_esi_datasource');
        $config->sso_scheme = config('eveapi.config.eseye_sso_scheme');
        $config->sso_host = config('eveapi.config.eseye_sso_host');
        $config->sso_port = config('eveapi.config.eseye_sso_port');
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
