<?php

namespace App\Http\Controllers\Original\PRCPWB\PRCPWB02;

use App\DataTables\Original\PRCPWB02\PRCPWBF003DataTable;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Inbox Forecast
class PRCPWBF003 extends Controller
{
    public function index(PRCPWBF003DataTable $dataTable)
    {
        return $dataTable->render('modules.PRCPWB.PRCPWB02.PRCPWBF003.PRCPWBF003');
    }

    public function detail($id)
    {
        $forecast = PRCPWB_TRHFORECAST::findOrFail($id);
        return view('...', compact('forecast'));
    }
}
