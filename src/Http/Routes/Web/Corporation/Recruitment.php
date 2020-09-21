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
use Seatplus\Eveapi\Http\Controllers\Corporation\Recruitment\DeleteApplicationController;
use Seatplus\Eveapi\Http\Controllers\Corporation\Recruitment\DeleteRecruitmentController;
use Seatplus\Eveapi\Http\Controllers\Corporation\Recruitment\DeleteUserApplicationController;
use Seatplus\Eveapi\Http\Controllers\Corporation\Recruitment\GetEnlistmentsController;
use Seatplus\Eveapi\Http\Controllers\Corporation\Recruitment\PostApplicationController;
use Seatplus\Eveapi\Http\Controllers\Corporation\Recruitment\PostCreateOpenRecruitmentController;

Route::prefix('recruitment')
    ->group(function () {
        Route::get('/list', GetEnlistmentsController::class)->name('list.open.enlistments');
        Route::post('/apply', PostApplicationController::class)->name('post.application');
        Route::delete('/application/character/{character_id}', DeleteApplicationController::class)->name('delete.character.application');
        Route::delete('/application/user/', DeleteUserApplicationController::class)->name('delete.user.application');

        Route::middleware(['permission:can open or close corporations for recruitment'])
            ->group(function () {
                Route::post('/', PostCreateOpenRecruitmentController::class)->name('create.corporation.recruitment');
                Route::delete('/{corporation_id}', DeleteRecruitmentController::class)->name('delete.corporation.recruitment');
            });
    });
