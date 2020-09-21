<?php

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
            ->group(function() {

                Route::post('/', PostCreateOpenRecruitmentController::class)->name('create.corporation.recruitment');
                Route::delete('/{corporation_id}', DeleteRecruitmentController::class)->name('delete.corporation.recruitment');
            });
    });

