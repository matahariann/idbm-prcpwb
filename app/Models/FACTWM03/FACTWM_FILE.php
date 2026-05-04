<?php

namespace App\Models\FACTWM03;

use App\Models\BaseModel;
use App\Models\HITUAM01\HITUAM_MSHUSER;

class FACTWM_FILE extends BaseModel
{
    protected $table = 'FACTWM_FILES';

    protected $fillable = [
        // 'VFILEABLE_TYPE',
        // 'VFILEABLE_ID',
        'VNAME',
        'VORIGINAL_NAME',
        'ISIZE',
        'VEXTENSION',
        'IFOLDER_ID',
        'IUSER_ID',
        'VPATH',
        'VFILE_TYPE',
        'VCREA',
        'DCREA',
        'DMODI',
        'VSUPPLIER_CODE',
        'VSUPPLIER_NAME',
    ];

    public function users()
    {
        return $this->belongsTo(HITUAM_MSHUSER::class, 'IUSER_ID', 'IID');
    }
}
