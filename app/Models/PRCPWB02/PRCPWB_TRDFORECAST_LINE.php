<?php

namespace App\Models\PRCPWB02;

use App\Models\BaseModel;

class PRCPWB_TRDFORECAST_LINE extends BaseModel
{
    protected $table = 'PRCPWB_TRDFORECASTLINES';
    protected $primaryKey = 'IID';
    
    public $incrementing = true;
    public $timestamps = false;

    const CREATED_AT = 'DCREA';
    const UPDATED_AT = 'DMODI';
    const DELETED_AT = 'DDELETE';

    protected $fillable = [
        'VPERIOD',
        'IREVNO',
        'VVENDORNO',
        'VPARTNO',
        'DRECEIPTDATE',
        'VDESCRIPTION',
        'EDUEQTY',
        'VUNITMEAS',
        'VDIMQUALITY',
        'EQTYONHAND',
        'EQTYTOORDERMAKER',
        'DETATOORDERMAKER',
        'VDESTINATIONID',
        'VCREA',
        'DCREA',
        'VMODI',
        'DMODI',
        'VDELETE',
        'DDELETE',
    ];

    protected $casts = [
        'DRECEIPTDATE' => 'datetime',
        'DETATOORDERMAKER' => 'datetime',
        'DCREA' => 'datetime',
        'DMODI' => 'datetime',
        'DDELETE' => 'datetime',
    ];
}
