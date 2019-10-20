<?php

return [

    'version'       => '1.0.0',

    // API Joblog logging
    'enable_joblog' => false,

    'redis_cache_location' => env('REDIS_HOST', '127.0.0.1'),
    'eseye_loglevel' => 'info', // valid entries are RFC 5424 levels ('debug', 'info', 'warn', 'error')
];
