<?php

namespace App\Models\FACTWM01;

use App\Models\BaseModel;

class FACTWM_MSHINFORMATION extends BaseModel
{

    // Sesuaikan dengan nama table di database
    // protected $connection = 'factwm';
    protected $table = 'FACTWM_MSHINFORMATION';
    protected $primaryKey = 'IID';
    const CREATED_AT = 'DCREA';

    const UPDATED_AT = 'DMODI';

    const DELETED_AT = 'DDELETE';
    protected $fillable = [
        'VNOTES',
        'DFROM',
        'DTO',
        'VUSER_TYPE',
        'VCATEGORY',
        'VFILE_INFORMATION',
        'VUPDLOAD_DATA_VENDOR',
        'VUPDLOAD_FOTO_ASSET',
        'VVIEWERS',
        'ITOTALVIEW',
        'VCREA',
        'DCREA',
        'VMODI',
        'DMODI',
        'VDELETE',
    ];
    protected $casts = [
        'DFROM' => 'datetime',
        'DTO' => 'datetime',
        'VVIEWERS' => 'array',
    ];
}
