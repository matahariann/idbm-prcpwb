<?php

namespace App\Models\FACTWM03;

use App\Models\BaseModel;

class FACTWM_LOGLOGIN_HISTORY extends BaseModel
{
    protected $table = 'FACTWM_LOGINHISTORY';

    protected $fillable = [
        'VUSERNAME',
        'VFULLNAME',
        'VEMAIL',
        'VUSERTYPE',
        'DLASTLOGIN',
        'VIPADDRESS',
        'VUSERAGENT',
        'BISACCEPTPRIVACY',
        'VCREA',
        'DCREA',
        'DMODI'
    ];
}
