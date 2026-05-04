<?php

namespace App\Http\Controllers\Original\FACTWM\FACTWM03;

use App\DataTables\Original\FACTWM03\FACTWMF014Datatable;
use App\Http\Controllers\Controller;

class FACTWMF014 extends Controller
{
    public function index(FACTWMF014Datatable $datatable)
    {
        return $datatable->render('modules.FACTWM.FACTWM03.FACTWMF014.FACTWMF014');
    }
}
