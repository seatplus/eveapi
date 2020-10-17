<?php

/*
 * MIT License
 *
 * Copyright (c) 2019, 2020 Felix Huber
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

use Illuminate\Support\Facades\Route;
use Seatplus\Eveapi\Http\Controllers\Alliance\InfoController as AllianceInfoController;
use Seatplus\Eveapi\Http\Controllers\Character\InfoController;
use Seatplus\Eveapi\Http\Controllers\Corporation\InfoController as CorporationInfoController;

Route::prefix('api-delete')
    ->group(function () {
        Route::get('scopes', fn () => config('eveapi.scopes.selected'))->middleware('auth:api');

        Route::prefix('character')
            ->group(function () {
                Route::get('info', [InfoController::class, 'index'])->name('get.character_info');

                Route::post('info', 'CharacterInfoController@update')->name('update.character_info');
                Route::post('assets', 'CharacterAssetController@update')->name('update.character.asset');
                Route::post('roles', 'CharacterRoleController@update')->name('update.character.role');
            });

        Route::prefix('corporation')->group(function () {
            Route::get('info', [CorporationInfoController::class, 'index'])->name('get.corporation_info');
        });

        Route::prefix('alliance')->group(function () {
            Route::get('info', [AllianceInfoController::class, 'index'])->name('get.alliance_info');
        });

        Route::post('/alliance_info', 'AllianceInfoController@update')->name('update.alliance_info');

        Route::post('/corporation_info', 'CorporationInfoController@update')->name('update.corporation_info');

        Route::namespace('Universe')
            ->prefix('universe')
            ->group(function () {
                Route::post('public_structures', 'PublicStructureController@update')->name('update.public_structures');
                Route::post('systems', 'SystemController@update')->name('update.systems');
            });
    });
