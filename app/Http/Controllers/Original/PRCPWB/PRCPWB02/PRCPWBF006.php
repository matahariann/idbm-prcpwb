<?php

namespace App\Http\Controllers\Original\PRCPWB\PRCPWB02;

use App\DataTables\Original\PRCPWB02\PRCPWBF006DataTable;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Stock
class PRCPWBF006 extends Controller
{
    public function index(PRCPWBF006DataTable $dataTable)
    {
        return $dataTable->render('modules.PRCPWB.PRCPWB02.PRCPWBF006.PRCPWBF006');
    }
}
