<?php

namespace App\Models\PRCPWB02;

use App\Models\BaseModel;

class PRCPWB_TRHFORECAST extends BaseModel
{
    protected $connection = 'prcpwb';
    protected $table = 'PRCPWB_TRHFORECASTS';
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
        'VDESTINATIONID',
        'VSTATUS',
        'VNOTES',
        'DRELEASEDATE',
        'VCONFIRMNOTES',
        'DCONFIRMDATE',
        'VCREA',
        'DCREA',
        'VMODI',
        'DMODI',
        'VDELETE',
        'DDELETE',
    ];

    protected $casts = [
        'VSTATUS' => \App\Enums\ForecastStatus::class,
        'DRELEASEDATE' => 'datetime',
        'DCONFIRMDATE' => 'datetime',
        'DCREA' => 'datetime',
        'DMODI' => 'datetime',
        'DDELETE' => 'datetime',
    ];
}
