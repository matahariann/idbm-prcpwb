<?php

namespace App\Models\HITUAM01;

use App\Models\BaseModel;

class HITUAM_MSHROLE_SERVICE extends BaseModel
{
    protected $connection = 'hituam';

    protected $table = 'HITUAM_MSHROLESERVICES';

    const CREATED_AT = 'DCREA';

    const UPDATED_AT = 'DMODI';

    protected $fillable = [
        'VROLE',
        'VSERVICE',
        'DBEGINEFF',
        'DENDEFF'
    ];

    public function role()
    {
        return $this->belongsTo(HITUAM_MSHROLE::class, 'VROLE', 'VROLENAME')
            ->whereNull('HITUAM_MSHROLES.DDELETE');
    }

    public function service()
    {
        return $this->belongsTo(HITUAM_MSHSERVICE::class, 'VSERVICE', 'VNAME');
    }
}
