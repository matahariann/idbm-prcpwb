<?php

namespace App\Models\PRCPWB02;

use App\Models\BaseModel;

class PRCPWB_TRHDELETED_DAILY_REQUEST extends BaseModel
{
    protected $connection = 'prcpwb';
    protected $table = 'PRCPWB_TRHDELETEDDAILYREQUESTS';
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
    ];

    protected $casts = [
        'VSTATUS' => \App\Enums\DailyRequestStatus::class,
        'VTIME' => 'time',
        'DWANTEDRECEIPTDATE' => 'date',
        'DPROPOSEDWANTEDRECEIPTDATE' => 'date',
    ];
}
