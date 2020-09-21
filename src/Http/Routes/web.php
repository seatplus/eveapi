<?php

use Illuminate\Support\Facades\Route;

Route::prefix('eveapi')
    ->middleware(['web', 'auth:sanctum'])
    ->group(function () {

        Route::prefix('corporation')->group(function () {
            include __DIR__ . '/Web/Corporation/Recruitment.php';
        });

    });
