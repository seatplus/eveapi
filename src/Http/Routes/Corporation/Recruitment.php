<?php

use Illuminate\Support\Facades\Route;
use Seatplus\Eveapi\Http\Controllers\Corporation\Recruitment\PostCreateOpenRecruitmentController;

Route::prefix('recruitment')
    ->group(function() {

        Route::middleware(['permission:can open or close corporations for recruitment'])
            ->group(function() {

                Route::post('/', PostCreateOpenRecruitmentController::class)->name('create.corporation.recruitment');

            });
    });
