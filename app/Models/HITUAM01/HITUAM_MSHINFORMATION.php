<?php

namespace App\Models\HITUAM01;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class HITUAM_MSHINFORMATION extends BaseModel
{
    use SoftDeletes;

    // Sesuaikan dengan nama table di database
    protected $connection = 'hituam';
    protected $table = 'HITUAM_MSHINFORMATION';
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
        'VCREA',
        'DCREA',
        'VMODI',
        'DMODI',
        'VDELETE',
    ];
    protected $casts = [
        'DFROM' => 'datetime',
        'DTO' => 'datetime',
    ];
}
