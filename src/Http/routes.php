<?php

use Illuminate\Support\Facades\Route;

Route::namespace('Seatplus\Eveapi\Http\Controllers\Updates')
    ->prefix('eveapi')
    ->group(function () {

        Route::post('/character_info', 'CharacterInfoController@update')->name('update.character_info');
        Route::post('/alliance_info', 'AllianceInfoController@update')->name('update.alliance_info');
        Route::post('/corporation_info', 'CorporationInfoController@update')->name('update.corporation_info');

    });
