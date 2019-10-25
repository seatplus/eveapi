<?php

Route::namespace('Seatplus\Eveapi\Http\Controllers\Jobs\Character')
    ->prefix('eveapi')
    ->group(function () {

        Route::post('/info', [
            'as' => 'info',
            'uses' => 'InfoController@update',
        ]);

    });
