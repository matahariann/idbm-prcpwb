<?php

namespace App\Models\HITUAM01;

use App\Models\BaseModel;
use App\Models\HITUAM01\HITUAM_MSHMENU as Menu;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HITUAM_MSHAPPLICATION extends BaseModel
{
    protected $connection = 'hituam';

    protected $table = 'HITUAM_MSHAPPLICATION';

    protected $fillable = [
        'VPROJECTDESC',
        'VDEPT',
        'VPIC',
        'VPORTALACCESS',
        'VPUBLISH',
        'VPORTALNAME',
        'VOPERATIONAL',
        'VSTRDZATION',
        'VPREFIXPROJECT',
        'VDATABASE',
        'NORDERPROJECT',
        'VICON',
        'BIS_EMBED',
        'VHOST', // SSO target URL for external applications.
    ];

    protected $casts = [
        'BIS_EMBED' => 'boolean',
    ];

    public function menus(): HasMany
    {
        return $this->hasMany(Menu::class, 'NSORTPROJECT', 'IID');
    }

    public function isExternal(): bool
    {
        return filled($this->VHOST);
    }
}
