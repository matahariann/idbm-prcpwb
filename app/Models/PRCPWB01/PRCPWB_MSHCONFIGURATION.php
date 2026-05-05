<?php

namespace App\Models\PRCPWB01;

use App\Models\BaseModel;

class PRCPWB_MSHCONFIGURATION extends BaseModel
{
    protected $connection = 'prcpwb';
    protected $table = 'PRCPWB_MSHCONFIGURATIONS';
    protected $primaryKey = 'IID';
    
    public $incrementing = true;
    public $timestamps = false;
 
    const CREATED_AT = 'DCREA';
    const UPDATED_AT = 'DMODI';
    const DELETED_AT = 'DDELETE';

    protected $fillable = [
        'VVARIABLE',
        'VVALUE',
        'VCREA',
        'DCREA',
        'VMODI',
        'DMODI',
        'VDELETE',
        'DDELETE',
    ];

    protected $casts = [
        'DCREA'  => 'datetime',
        'DMODI'  => 'datetime',
        'DDELETE' => 'datetime',
    ];
}
