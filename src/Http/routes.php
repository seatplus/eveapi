<?php

use Illuminate\Support\Facades\Route;

Route::namespace('Seatplus\Eveapi\Http\Controllers\Updates')
    ->prefix('eveapi')
    ->group(function () {

        Route::get('scopes', function () {

            return config('eveapi.scopes.selected');

        })->middleware('auth:api');

        Route::prefix('character')
            ->group(function () {

                Route::post('info', 'CharacterInfoController@update')->name('update.character_info');
                Route::post('assets', 'CharacterAssetController@update')->name('update.character.asset');
                Route::post('roles', 'CharacterRoleController@update')->name('update.character.role');
            });

        Route::post('/alliance_info', 'AllianceInfoController@update')->name('update.alliance_info');
        Route::post('/corporation_info', 'CorporationInfoController@update')->name('update.corporation_info');

    });
