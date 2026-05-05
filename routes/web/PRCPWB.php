<?php

use App\Models\HITUAM01\HITUAM_MSHUSER as User;
use App\Http\Controllers\Original\PRCPWB\PRCPWB01\PRCPWBF001;
use App\Http\Controllers\Original\PRCPWB\PRCPWB02\PRCPWBF002;
use App\Http\Controllers\Original\PRCPWB\PRCPWB02\PRCPWBF003;
use App\Http\Controllers\Original\PRCPWB\PRCPWB02\PRCPWBF004;
use App\Http\Controllers\Original\PRCPWB\PRCPWB02\PRCPWBF005;
use App\Http\Controllers\Original\PRCPWB\PRCPWB02\PRCPWBF006;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'bd', 'middleware' => 'auth'], function () {
    Route::group(['prefix' => 'configuration', 'controller' => PRCPWBF001::class], function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{configuration}', 'show');
        Route::put('/{configuration}', 'update');
        Route::delete('/{configuration}', 'destroy');
    });
});

Route::group(['prefix' => 'ts', 'middleware' => 'auth'], function () {
    Route::group(['prefix' => 'inbox-forecast', 'controller' => PRCPWBF002::class], function () {
        Route::get('/', 'index');
    });

    Route::group(['prefix' => 'inbox-po', 'controller' => PRCPWBF003::class], function () {
        Route::get('/', 'index');
    });

    Route::group(['prefix' => 'daily-request', 'controller' => PRCPWBF004::class], function () {
        Route::get('/', 'index');
    });

    Route::group(['prefix' => 'data-stock', 'controller' => PRCPWBF005::class], function () {
        Route::get('/', 'index');
    });

    Route::group(['prefix' => 'generate-qr', 'controller' => PRCPWBF006::class], function () {
        Route::get('/', 'index');
    });
});