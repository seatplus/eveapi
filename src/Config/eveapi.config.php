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

return [

    'version'       => '1.0.0',

    // API Joblog logging
    'enable_joblog' => false,

    'eseye_logfile'         => storage_path('logs'),
    'eseye_cache'           => storage_path('eseye'),
    'eseye_loglevel' => 'info', // valid entries are RFC 5424 levels ('debug', 'info', 'warn', 'error')

    'eseye_esi_scheme'      => env('EVE_ESI_SCHEME', 'https'),
    'eseye_esi_host'        => env('EVE_ESI_HOST', 'esi.evetech.net'),
    'eseye_esi_port'        => env('EVE_ESI_PORT', 443),
    'eseye_esi_datasource'  => env('EVE_ESI_DATASOURCE', 'tranquility'),
    'eseye_sso_scheme'      => env('EVE_SSO_SCHEME', 'https'),
    'eseye_sso_host'        => env('EVE_SSO_HOST', 'login.eveonline.com'),
    'eseye_sso_port'        => env('EVE_SSO_PORT', 443),
];
