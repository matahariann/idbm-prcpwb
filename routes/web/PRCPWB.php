<?php

use App\Models\HITUAM01\HITUAM_MSHUSER as User;
use App\Http\Controllers\Original\PRCPWB\PRCPWB01\PRCPWBF001;
use App\Http\Controllers\Original\PRCPWB\PRCPWB01\PRCPWBF002;
use App\Http\Controllers\Original\PRCPWB\PRCPWB02\PRCPWBF003;
use App\Http\Controllers\Original\PRCPWB\PRCPWB02\PRCPWBF004;
use App\Http\Controllers\Original\PRCPWB\PRCPWB02\PRCPWBF005;
use App\Http\Controllers\Original\PRCPWB\PRCPWB02\PRCPWBF006;
use App\Http\Controllers\Original\PRCPWB\PRCPWB02\PRCPWBF007;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'bd', 'middleware' => 'auth'], function () {
    // Configuration
    Route::group(['prefix' => 'configuration', 'controller' => PRCPWBF001::class], function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{configuration}', 'show');
        Route::put('/{configuration}', 'update');
        Route::delete('/{configuration}', 'destroy');
    });

    // Master Vendor
    Route::group(['prefix' => 'master-vendor', 'controller' => PRCPWBF002::class], function () {
        Route::get('/', 'index');
        Route::get('/export', 'export');
        Route::post('/export', 'export');
        Route::get('/{vendor}', 'show');
        Route::put('/{vendor}', 'update');
    });
});

Route::group(['prefix' => 'ts', 'middleware' => 'auth'], function () {
    // Inbox Forecast
    Route::group(['prefix' => 'inbox-forecast', 'controller' => PRCPWBF003::class], function () {
        Route::get('/', 'index');
        Route::get('/{id}', 'detail')->name('forecast.detail');
    });

    // Inbox PO
    Route::group(['prefix' => 'inbox-po', 'controller' => PRCPWBF004::class], function () {
        Route::get('/', 'index');
        Route::get('/{id}', 'detail')->name('po.detail');
    });

    // Daily Request
    Route::group(['prefix' => 'daily-request', 'controller' => PRCPWBF005::class], function () {
        Route::get('/', 'index');
        Route::get('/export', 'export');
        Route::post('/export', 'export');
    });

    // Stock
    Route::group(['prefix' => 'data-stock', 'controller' => PRCPWBF006::class], function () {
        Route::get('/', 'index');
        Route::get('/export', 'export');
        Route::post('/export', 'export');
    });

    // Generate QR
    Route::group(['prefix' => 'generate-qr', 'controller' => PRCPWBF007::class], function () {
        Route::get('/', 'index');
    });
});