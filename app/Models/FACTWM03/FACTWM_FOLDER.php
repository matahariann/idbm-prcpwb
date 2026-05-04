<?php

namespace App\Models\FACTWM03;

use App\Models\BaseModel;
use App\Models\HITUAM01\HITUAM_MSHUSER;

class FACTWM_FOLDER extends BaseModel
{
    protected $table = 'FACTWM_FOLDERS';

    protected $fillable = [
        'VNAME',
        'VSUPPLIER_CODE',
        'VSUPPLIER_NAME',
        'IPARENT_ID',
        'ISIZE',
        'ITOTAL_FILES',
        'VFOLDER_TYPE',
        'IUSER_ID',
        'VCREA',
        'DCREA',
        'DMODI'
    ];


    public function users()
    {
        return $this->belongsTo(HITUAM_MSHUSER::class, 'IUSER_ID', 'IID');
    }
}
