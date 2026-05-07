<?php

namespace App\Models\PRCPWB02;

use App\Models\BaseModel;

class PRCPWB_TRHDAILY_REQUEST extends BaseModel
{
    protected $connection = 'prcpwb';
    protected $table = 'PRCPWB_TRHDAILYREQUESTS';
    protected $primaryKey = 'IID';

    public $incrementing = true;
    public $timestamps = false;

    const CREATED_AT = 'DCREA';
    const UPDATED_AT = 'DMODI';
    const DELETED_AT = 'DDELETE';

    protected $fillable = [
        'VVENDORNO',
        'VPARTNO',
        'VPARTDESCRIPTION',
        'DWANTEDRECEIPTDATE',
        'DPROPOSEDWANTEDRECEIPTDATE',
        'VTIME',
        'IQUANTITY',
        'IQUANTITYCONFIRMATION',
        'IQUANTITYACTUAL',
        'VSTATUS',
        'VDELIVERYNOTENO',
        'VPONO',
        'VDAILYREQNO',
        'VPRODUCTFAMILY',
        'IREVNO',
        'VFORECAST',
        'IMSPERIOD',
        'IMSYEAR',
        'VUNITMEAS',
        'VDEDICATEDLOCATION',
        'VPROCCONTACT',
        'VCREA',
        'DCREA',
        'VMODI',
        'DMODI',
        'VDELETE',
        'DDELETE',
    ];

    protected $casts = [
        'VSTATUS' => \App\Enums\DailyRequestStatus::class,
        'DWANTEDRECEIPTDATE' => 'date',
        'DPROPOSEDWANTEDRECEIPTDATE' => 'date',
        'DCREA' => 'datetime',
        'DMODI' => 'datetime',
        'DDELETE' => 'datetime',
    ];
}
