<?php

namespace App\Models\FACTWM01;

use App\Models\BaseModel;

class FACTWM_MSHCHANGE_REQUEST_VENDOR extends BaseModel
{
    protected $table = 'FACTWM_MSHCHANGE_REQUEST_VENDOR';

    protected $fillable = [
        'ICOMM_ID',
        'VSUPPLIER_CODE',
        'VSUPPLIER_NAME',
        'VNAME',
        'VUSERNAME',
        'VMETHOD_ID',
        'VDESCRIPTION',
        'VADDRESS_ID',
        'VVALUE',
        'VPARTY_TYPE_DB_VAL',
        'BMETHOD_DEFAULT',
        'IUSER_ID',
        'ISUPPLIER_ID',
        'VSTATUS',
        'VTYPE',
        'BDOWNLOAD'
    ];

    protected $casts = [
        'VSTATUS' => \App\Enums\ChangeRequestStatus::class,
        'VTYPE' => \App\Enums\ChangeRequestType::class,
        'DCREA' => 'datetime',
    ];
}
