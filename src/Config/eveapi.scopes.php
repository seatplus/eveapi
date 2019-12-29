<?php

return [

    'minimum' => ['publicData'],
    'maximum' => ['publicData', 'esi-characters.read_titles.v1',
        // Corp-Job required
        'esi-characters.read_corporation_roles.v1',
        // Assets required
        'esi-assets.read_assets.v1',  'esi-universe.read_structures.v1'
    ],
];
