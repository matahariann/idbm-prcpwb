<?php

namespace App\Http\Controllers\Original\PRCPWB\PRCPWB02;

use App\DataTables\Original\PRCPWB02\PRCPWBF005DataTable;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Daily Request
class PRCPWBF005 extends Controller
{
    public function index(PRCPWBF005DataTable $dataTable)
    {
        return $dataTable->render('modules.PRCPWB.PRCPWB02.PRCPWBF005.PRCPWBF005');
    }
}
