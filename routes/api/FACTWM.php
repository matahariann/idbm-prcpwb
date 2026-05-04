<?php

use App\Http\Controllers\Api\FACTWM\FACTWM01\FACTWMF002;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FACTWM\FACTWM02\FACTWMF006;
use App\Http\Controllers\Api\FACTWM\FACTWM02\FACTWMF007;
use App\Http\Controllers\Api\FACTWM\FACTWM02\FACTWMF009;
use App\Http\Controllers\Api\FACTWM\FACTWM03\FACTWMF011;
use App\Http\Controllers\Original\FACTWM\FACTWM01\FACTWMF001;

Route::group(['prefix' => 'bd', 'middleware' => 'auth:sanctum'], function () {

    Route::group(['prefix' => 'configuration', 'controller' => FACTWMF001::class], function () {
        Route::get('/variable', 'showByVariable');
    });

    Route::group(['prefix' => 'master-vendor', 'controller' => FACTWMF002::class], function () {
        Route::post('/store', 'store');
        Route::delete('/delete/{supplierId}', 'destroy');
    });
});

Route::group(['prefix' => 'bd'], function () {

    Route::group(['prefix' => 'configuration', 'controller' => FACTWMF001::class], function () {
        Route::get('/variable', 'showByVariable');
    });
});


Route::group(['prefix' => 'ts'], function () {
    Route::group(['prefix' => 'verify-po', 'controller' => FACTWMF007::class], function () {
        Route::post('/validate-invoice', 'validateInvoice');
        Route::post('/validate-rekap-jasa', 'validateRekapJasa');
        Route::post('/validate-tax', 'validateTax');
    });
});

Route::group(['prefix' => 'ts'], function () {
    Route::group(['prefix' => 'scan-verify-non-po', 'controller' => FACTWMF009::class], function () {
        Route::post('/send-email', 'sendEmail');
    });
});

// api yang idbm hit tambahkan validasi token
Route::middleware(['check.token'])->group(function () {
    Route::group(['prefix' => 'ts'], function () {

        Route::group(['prefix' => 'good-receipt-notes', 'controller' => FACTWMF006::class], function () {
            Route::post('/store', 'store');
            Route::put('/update', 'update');
            Route::delete('/delete', 'destroy');
        });
    });

    Route::group(['prefix' => 'rt'], function () {
        Route::group(['prefix' => 'report-invoice', 'controller' => FACTWMF011::class], function () {
            Route::post('/store', 'store');
        });
    });
});
