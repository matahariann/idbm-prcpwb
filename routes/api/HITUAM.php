<?php

use App\Http\Controllers\Api\HITUAM\HITUAM02\HITUAMF009;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'auth', 'controller' => HITUAMF009::class], function () {
    Route::post('/login', 'login');

    Route::group(['middleware' => 'auth:sanctum'], function () {
        Route::post('/logout', 'logout');
    });
});
