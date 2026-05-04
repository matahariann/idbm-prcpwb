<?php

use App\Models\HITUAM01\HITUAM_MSHUSER as User;
use App\Http\Controllers\Original\FACTWM\FACTWM01\FACTWMF001;
use App\Http\Controllers\Original\FACTWM\FACTWM01\FACTWMF002;
use App\Http\Controllers\Original\FACTWM\FACTWM01\FACTWMF003;
use App\Http\Controllers\Original\FACTWM\FACTWM01\FACTWMF004;
use App\Http\Controllers\Original\FACTWM\FACTWM01\FACTWMF005;
use App\Http\Controllers\Original\FACTWM\FACTWM02\FACTWMF006;
use App\Http\Controllers\Original\FACTWM\FACTWM02\FACTWMF007;
use App\Http\Controllers\Original\FACTWM\FACTWM02\FACTWMF008;
use App\Http\Controllers\Original\FACTWM\FACTWM02\FACTWMF009;
use App\Http\Controllers\Original\FACTWM\FACTWM03\FACTWMF010;
use App\Http\Controllers\Original\FACTWM\FACTWM03\FACTWMF011;
use App\Http\Controllers\Original\FACTWM\FACTWM03\FACTWMF012;
use App\Http\Controllers\Original\FACTWM\FACTWM03\FACTWMF013;
use App\Http\Controllers\Original\FACTWM\FACTWM03\FACTWMF014;
use App\Http\Controllers\Original\FACTWM\FACTWM04\FACTWMF015;
use App\Http\Controllers\Original\FACTWM\FACTWM04\FACTWMF016;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'bd', 'middleware' => 'auth'], function () {
    Route::group(['prefix' => 'configuration', 'controller' => FACTWMF001::class], function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{configuration}', 'show');
        Route::put('/{configuration}', 'update');
        Route::delete('/{configuration}', 'destroy');
    });

    Route::group(['prefix' => 'master-vendor', 'controller' => FACTWMF002::class], function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/template', 'template');
        Route::post('/import', 'import');
        Route::get('/export', 'export');
        Route::get('/{vendor}', 'show');
        Route::put('/{vendor}', 'update');
        Route::delete('/{vendor}', 'destroy');
    });

    Route::group(['prefix' => 'change-request-vendor', 'controller' => FACTWMF003::class], function () {
        Route::get('/', 'index');
        Route::get('/request-table', 'requestTable');
        Route::post('/', 'store');
        Route::post('/submit-request', 'submitRequest');
        Route::get('/{request}', 'show');
        Route::put('/', 'update');
        Route::delete('/{request}', 'destroy');
    });

    Route::group(['prefix' => 'master-news', 'controller' => FACTWMF004::class], function () {
        Route::get('/', 'index')->name('factwm.master-news.index');
        Route::get('/form/{news}', 'storeForm');
        Route::post('/bulk-delete', 'bulkDelete');
        Route::post('/', 'store');
        Route::post('/{news}', 'update');
        Route::get('/id/{news}', 'showNewsById');
        Route::get('/slug/{news}', 'show');
        Route::delete('/{news}', 'destroy');
    });

    Route::group(['prefix' => 'master-information', 'as' => 'information.', 'controller' => FACTWMF005::class], function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/bulk-delete', 'bulkDelete');
        Route::post('/', 'store')->name('store');
        Route::get('/update/{information}', 'edit')->name('edit');
        Route::put('/{information}', 'update')->name('update');
        Route::get('/{information}', 'show')->name('show');
        Route::delete('/{information}', 'destroy')->name('destroy');
    });
});

Route::group(['prefix' => 'ds', 'middleware' => 'auth'], function () {
    Route::group(['prefix' => 'news', 'controller' => FACTWMF015::class], function () {
        Route::get('/', 'index')->name('factwm.news.index');
        Route::get('/filter', 'filterNews')->name('factwm.news.filter');
        Route::get('/{idViewers}/{slug}', 'showNewsByViewer')->name('factwm.news.show');
    });

    Route::group(['prefix' => 'informations', 'controller' => FACTWMF016::class], function () {
        Route::get('/', 'index')->name('factwm.dashboard-information.index');
        Route::post('/close', 'close')->name('factm.dashboard-information.close');
    });
});

Route::group(['prefix' => 'ts', 'middleware' => 'auth'], function () {
    Route::group(['prefix' => 'good-receipt-notes', 'controller' => FACTWMF006::class], function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/export', 'export')->name('good-receipt-notes.export');
        Route::post('/dispute', 'dispute')->name('good-receipt-notes.dispute');
        Route::get('/{grNotes}', 'show');
        Route::put('/{grNotes}', 'update');
        Route::delete('/{grNotes}', 'destroy');
        Route::post('/approve/{grNotes}', 'toggleApprove');
        Route::post('/approve-dispute/{grNotes}', 'toggleApproveDispute');
        Route::post('/reject-dispute/{grNotes}', 'toggleRejectDispute');
        Route::post('/approve-multiple', 'toggleApproveMultiple');
        Route::post('/summary', 'getSummary');
    });

    Route::group(['prefix' => 'verify-po', 'controller' => FACTWMF007::class], function () {
        Route::get('/', 'index');
        Route::get('/view', 'view');
        Route::get('/view-table', 'viewTable');
        Route::get('/selectable-grns', 'selectableGrns');
        Route::get('/ocr', 'ocr');
        Route::get('/draft-last', 'draftLast');
        Route::get('/ocr-table', 'ocrTable');
        Route::get('/get-idbm-npwp-match', 'getIdbmNpwpMatch');
        Route::get('/final-preview/{verifyPo}', 'finalPreview');
        Route::get('/download/{verifyPo}/{type}', 'download')->name('verify-po.download');
        Route::get('/download-other-file/{other}', 'downloadOtherFile')->name('verify-po.download-other-file');
        Route::get('/preview-pdf/{verifyPo}', 'previewPdf');
        Route::get('/preview-pdf-grn/{grn}', 'previewPdfGRN');

        Route::post('/', 'store');
        Route::post('/remove-gr', 'removeGR');
        Route::post('/validate-invoice', 'validateInvoice');
        Route::post('/validate-rekap-jasa', 'validateRekapJasa');
        Route::post('/validate-tax', 'validateTax');
        Route::post('/clear-ocr-state', 'clearOcrState');
        Route::post('/submit-final-preview/{verifyPo}', 'submitFinalPreview');
        Route::post('/reset-si/{po}', 'resendSI')->whereNumber('po');

        Route::put('/{po}', 'update');
        Route::delete('/{po}', 'destroy');

        // ✅ Wildcard GET last — so it doesn't shadow anything above
        Route::get('/{po}', 'show');
    });

    Route::group(['prefix' => 'verify-non-po', 'as' => 'verify-non-po.', 'controller' => FACTWMF008::class], function () {
        Route::get('/', 'index')->name('index');
        Route::get('/export', 'export')->name('export');
        Route::get('/create', 'create')->name('create');
        Route::get('/draft-last', 'draftLast');
        Route::get('/edit/{nonPo}', 'edit')->whereNumber('nonPo')->name('edit');
        Route::get('/view/{nonPo}', 'view')->whereNumber('nonPo')->name('view');
        Route::post('/', 'store')->name('store');
        Route::put('/{nonPo}', 'update')->whereNumber('nonPo')->name('update');
        Route::delete('/{nonPo}', 'destroy')->whereNumber('nonPo');
        Route::post('/validate-invoice', 'validateInvoice');
        Route::post('/validate-tax', 'validateTax');
        Route::post('/clear-ocr-state', 'clearOcrState');
        Route::get('/download/{verifyPo}/{type}', 'download')->whereNumber('verifyPo')
            ->name('download');
        Route::get('/download-other-file/{other}', 'downloadOtherFile')->whereNumber('other')->name('download-other-file');
        Route::get('/final-preview/{verifyPo}', 'finalPreview')->whereNumber('verifyPo');
        Route::get('/preview-pdf/{verifyPo}', 'previewPdf')->whereNumber('verifyPo');
        Route::post('/submit-final-preview/{verifyPo}', 'submitFinalPreview')->whereNumber('verifyPo');
        Route::post('/reset-si/{po}', 'resendSI');
        Route::get('/{nonPo}', 'show')->whereNumber('nonPo');
    });

    Route::group(['prefix' => 'scan-verify-non-po', 'controller' => FACTWMF009::class], function () {
        Route::get('/', 'index');
        Route::post('/check-billing', 'checkByBilling');
        Route::post('/check-unique-code', 'checkByUniqueCode');
        Route::get('/history', 'history');
        Route::post('/submit', 'submit');
    });
});

Route::group(['prefix' => 'rt', 'middleware' => 'auth'], function () {
    Route::group(['prefix' => 'log-histories', 'controller' => FACTWMF014::class], function () {
        Route::get('/', 'index');
    });

    Route::group(['prefix' => 'gr-notes', 'controller' => FACTWMF010::class], function () {
        Route::get('/', 'index');
        Route::get('/export', 'export');
        Route::get('/{id}', 'show');
        Route::post('/{id}/approve', 'approve');
        Route::get('/{id}/items', 'getItems');
    });

    Route::group(['prefix' => 'document-managements', 'controller' => FACTWMF013::class], function () {
        Route::get('/', 'index')->name('document-managements.index');
        Route::get('/dataTable', 'dataTable')->name('document-managements.dataTable');
        Route::get('/getData', 'getData')->name('document-managements.getData');
        Route::get('/getFileProgress', 'getFileProgress')->name('document-managements.getFileProgress');
        Route::get('/getDataChart', 'getDataChart')->name('document-managements.getDataChart');
        Route::get('/download/{id}', 'downloadFile')->name('document-managements.download');
        Route::post('/upload', 'upload')->name('document-managements.upload');
        Route::post('/uploadOtherDocument', 'uploadOtherDocument')->name('document-managements.uploadOtherDocument');
    });

    Route::group(['prefix' => 'invoices', 'controller' => FACTWMF011::class], function () {
        Route::get('/', 'index')->name('invoices.index');
        Route::get('/export', 'export')->name('invoices.export');
        Route::get('/detail/{IID}', 'getDetail')->name('invoices.getDetail');
        Route::post('/approve/{IID}', 'approveInvoice')->name('invoices.approve');
        Route::post('/reject/{IID}', 'rejectInvoice')->name('invoices.reject');
    });

    Route::group(['prefix' => 'overview', 'controller' => FACTWMF012::class], function () {
        Route::get('/', 'index')->name('overview.index');
        Route::get('/export', 'export')->name('overview.export');
        Route::get('/detail/{IID}', 'getDetail')->name('overview.getDetail');
    });
});
