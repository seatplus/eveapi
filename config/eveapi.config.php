<?php

use Monolog\Level;

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

    'esi-client' => [
        // Logging only. The ESI/SSO connection (datasource, scheme, host, port) and the
        // X-Compatibility-Date header are esi-client's own concern — the base URL is
        // esi.evetech.net and the compatibility date is pinned to the installed
        // esi-client/esi-schema version, so neither is application-configurable.
        'logger_level' => Level::Info->value, // Monolog\Level case (Debug/Info/Warning/Error/…)
        'logfile_location' => storage_path('logs'),
    ],

    'esi' => [
        'eve_client_id' => env('EVE_CLIENT_ID'),
        'eve_client_secret' => env('EVE_CLIENT_SECRET'),
    ],
    'queue' => [
        'balancing_mode' => env('QUEUE_BALANCING_MODE', false),
        'workers' => (int) env('QUEUE_WORKERS', 4),
    ],
];
