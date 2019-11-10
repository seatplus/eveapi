<?php

return [

    'version'       => '1.0.0',

    // API Joblog logging
    'enable_joblog' => false,

    'eseye_logfile'         => storage_path('logs'),
    'eseye_loglevel' => 'info', // valid entries are RFC 5424 levels ('debug', 'info', 'warn', 'error')
];
