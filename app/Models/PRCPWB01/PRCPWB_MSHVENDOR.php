<?php

namespace App\Models\PRCPWB01;

use Illuminate\Database\Eloquent\Model;

class PRCPWB_MSHVENDOR extends Model
{
    protected $connection = 'prcpwb';
    protected $table = 'PRCPWB_MSHVENDORS';
    protected $primaryKey = 'IID';
    
    public $incrementing = true;
    public $timestamps = false;
 
    const CREATED_AT = 'DCREA';
    const UPDATED_AT = 'DMODI';
    const DELETED_AT = 'DDELETE';

    protected $fillable = [
        'VVENDORNO',
        'VVENDORNAME',
        'VCONTACT',
        'VADDRESS',
        'VIMPORT',
        'VCREA',
        'DCREA',
        'VMODI',
        'DMODI',
        'VDELETE',
        'DDELETE',
    ];

    protected $casts = [
        'VIMPORT' => 'boolean',
        'DCREA'  => 'datetime',
        'DMODI'  => 'datetime',
        'DDELETE' => 'datetime',
    ];
}
