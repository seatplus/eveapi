<?php

use Illuminate\Support\Facades\Http;
use Seatplus\Eveapi\Services\EsiPathService;

it('can get esi paths', function () {

    Http::fake([
        'https://esi.evetech.net/latest/swagger.json' => Http::response([
            'paths' => [
                '/characters/{character_id}/' => [
                    'get' => [
                        'tags' => [
                            'Character',
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $esi_path_service = new EsiPathService;

    expect($esi_path_service->getEsiPaths())->toBeArray();
});
