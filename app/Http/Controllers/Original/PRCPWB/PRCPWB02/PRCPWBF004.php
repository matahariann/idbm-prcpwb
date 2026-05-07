<?php

namespace App\Http\Controllers\Original\PRCPWB\PRCPWB02;

use App\DataTables\Original\PRCPWB02\PRCPWBF004DataTable;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Inbox PO
class PRCPWBF004 extends Controller
{
    public function index(PRCPWBF004DataTable $dataTable)
    {
        return $dataTable->render('modules.PRCPWB.PRCPWB02.PRCPWBF004.PRCPWBF004');
    }

    public function detail($id)
    {
        $forecast = PRCPWB_TRHPO::findOrFail($id);
        return view('...', compact('po'));
    }
}
