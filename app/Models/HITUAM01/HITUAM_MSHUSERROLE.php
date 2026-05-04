<?php

namespace App\Models\HITUAM01;

use App\Models\BaseModel;
use App\Models\HITUAM01\HITUAM_MSHMENU as Menu;
use App\Models\HITUAM01\HITUAM_MSHROLE as Role;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class HITUAM_MSHUSERROLE extends BaseModel
{
    protected $connection = 'hituam';

    protected $table = 'HITUAM_MSHUSERROLES';

    protected $fillable = [
        'VUSERNAME',
        'VROLE',
    ];
}
