<?php

namespace App\Http\Controllers\Original\PRCPWB\PRCPWB01;

use App\DataTables\Original\PRCPWB01\PRCPWBF002DataTable;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Master Vendor
class PRCPWBF002 extends Controller
{
    public function index(PRCPWBF002DataTable $dataTable)
    {
        return $dataTable->render('modules.PRCPWB.PRCPWB01.PRCPWBF002.PRCPWBF002');
    }
}
