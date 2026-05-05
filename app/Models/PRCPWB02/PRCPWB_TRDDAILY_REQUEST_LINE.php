<?php

namespace App\Models\PRCPWB02;

use App\Models\BaseModel;

class PRCPWB_TRDDAILY_REQUEST_LINE extends BaseModel
{
    protected $connection = 'prcpwb';
    protected $table = 'PRCPWB_TRHDAILYREQUESTLINES';
    protected $primaryKey = 'IID';

    public $incrementing = true;
    public $timestamps = false;

    const CREATED_AT = 'DCREA';
    const UPDATED_AT = 'DMODI';
    const DELETED_AT = 'DDELETE';

    protected $fillable = [
        'VVENDORNO',
        'DWANTEDRECEIPTDATE',
        'VPONO',
        'VTIME',
        'VDELIVERYNOTENO',
        'VCREA',
        'DCREA',
        'VMODI',
        'DMODI',
        'VDELETE',
        'DDELETE',
    ];

    protected $casts = [
        'DWANTEDRECEIPTDATE' => 'date',
        'DCREA' => 'datetime',
        'DMODI' => 'datetime',
        'DDELETE' => 'datetime',
    ];
}
