<?php

namespace App\Models\PRCPWB02;

use App\Models\BaseModel;

class PRCPWB_TRDPO_LINE extends BaseModel
{
    protected $connection = 'prcpwb';
    protected $table = 'PRCPWB_TRDPOLINES';
    protected $primaryKey = 'IID';
    
    public $incrementing = true;
    public $timestamps = false;
    
    const CREATED_AT = 'DCREA';
    const UPDATED_AT = 'DMODI';
    const DELETED_AT = 'DDELETE';

    protected $fillable = [
        'VPONO',
        'IREVISIONNO',
        'VLINENO',
        'VRELEASENO',
        'VPARTNO',
        'VDESCRIPTION',
        'EBUYQTYDUE',
        'VBUYUNITMEAS',
        'EBUYUNITPRICE',
        'EAMOUNT',
        'VREQUISITIONNO',
        'VCREA',
        'DCREA',
        'VMODI',
        'DMODI',
        'VDELETE',
        'DDELETE',
    ];

    protected $casts = [
        'DCREA' => 'datetime',
        'DMODI' => 'datetime',
        'DDELETE' => 'datetime',
    ];
}
