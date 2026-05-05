<?php

namespace App\Models\PRCPWB02;

use App\Models\BaseModel;

class PRCPWB_TRHPO extends BaseModel
{
    protected $connection = 'prcpwb';
    protected $table = 'PRCPWB_TRHPO';
    protected $primaryKey = 'IID';

    public $incrementing = true;
    public $timestamps = false;

    const CREATED_AT = 'DCREA';
    const UPDATED_AT = 'DMODI';
    const DELETED_AT = 'DDELETE';

    protected $fillable = [
        'VORDERNO',
        'IREVISIONNO',
        'VVENDORNO',
        'VSTATUS',
        'DRELEASEDATE',
        'DGETDATE',
        'VCONFIRMTEXT',
        'DCONFIRMDATE',
        'DDATEENTERED',
        'VDELIVERYBY',
        'DWANTEDDELIVERYDATE',
        'DWANTEDRECEIPTDATE',
        'EVAT',
        'VREMARK',
        'VCURRENCYCODE',
        'VDELTERMS',
        'VDESTINATION',
        'VCREA',
        'DCREA',
        'VMODI',
        'DMODI',
        'VDELETE',
        'DDELETE',
    ];

    protected $casts = [
        'VSTATUS' => \App\Enums\POStatus::class,
        'DRELEASEDATE' => 'datetime',
        'DGETDATE' => 'datetime',
        'DCONFIRMDATE' => 'datetime',
        'DDATEENTERED' => 'datetime',
        'DWANTEDDELIVERYDATE' => 'datetime',
        'DWANTEDRECEIPTDATE' => 'datetime',
        'DCREA' => 'datetime',
        'DMODI' => 'datetime',
        'DDELETE' => 'datetime',
    ];
}
