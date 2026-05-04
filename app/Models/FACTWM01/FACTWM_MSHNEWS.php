<?php

namespace App\Models\FACTWM01;

use App\Models\BaseModel;

/**
 * @method static \Illuminate\Database\Eloquent\Builder filtered($request)
 */
class FACTWM_MSHNEWS extends BaseModel
{
    protected $table = 'FACTWM_MSHNEWS';

    protected $fillable = [
        'VTITLE',
        'VSUBJECT',
        'AVIEWERS',
        'ITOTALVIEW',
        'VCONTENT',
        'VIMAGE_PATH',
        'VFILE_PATH',
        'BSTATUS',
        'DPUBLISHED_AT',
        'VCREA',
        'DCREA',
        'VMODI',
        'DMODI',
        'VDELETE',
        'DDELETE'
    ];

    protected $casts = [
        'AVIEWERS' => 'array',
    ];
}
