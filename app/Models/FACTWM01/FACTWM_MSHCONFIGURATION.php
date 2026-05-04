<?php

namespace App\Models\FACTWM01;

use App\Models\BaseModel;

class FACTWM_MSHCONFIGURATION extends BaseModel
{
    protected $table = 'FACTWM_MSHCONFIGURATIONS';

    protected $fillable = [
        'VVARIABLE',
        'VVALUE'
    ];
}
