<?php

use Illuminate\Support\Facades\Route;

Route::namespace('Seatplus\Eveapi\Http\Controllers\Updates')
    ->prefix('eveapi')
    ->group(function () {

        Route::prefix('character')
            ->group(function () {

                Route::post('info', 'CharacterInfoController@update')->name('update.character_info');
                Route::post('role', 'CharacterRoleController@update')->name('update.character.role');
            });

        Route::post('/alliance_info', 'AllianceInfoController@update')->name('update.alliance_info');
        Route::post('/corporation_info', 'CorporationInfoController@update')->name('update.corporation_info');

    });
