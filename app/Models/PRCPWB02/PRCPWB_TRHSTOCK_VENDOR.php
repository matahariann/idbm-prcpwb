<?php

namespace App\Models\PRCPWB02;

use App\Models\BaseModel;

class PRCPWB_TRHSTOCK_VENDOR extends BaseModel
{
    protected $connection = 'prcpwb';
    protected $table = PRCPWB_TRHSTOCKVENDORS;
    protected $primaryKey = 'IID';
    
    public $incrementing = true;
    public $timestamps = false;

    const CREATED_AT = 'DCREA';
    const UPDATED_AT = 'DMODI';
    const DELETED_AT = 'DDELETE';

    protected $fillable = [
        'VVENDORNO',
        'VPARTNO',
        'EQTYONHAND',
        'DUPLOADDATE',
        'VREMARK',
        'VCREA',
        'DCREA',
        'VMODI',
        'DMODI',
        'VDELETE',
        'DDELETE',
    ];

    protected $casts = [
        'DUPLOADDATE'  => 'datetime',
        'DCREA'        => 'datetime',
        'DMODI'        => 'datetime',
        'DDELETE'      => 'datetime',
    ];
}
