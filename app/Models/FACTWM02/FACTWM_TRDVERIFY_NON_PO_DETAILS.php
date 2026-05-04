<?php

namespace App\Models\FACTWM02;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @method static \Illuminate\Database\Eloquent\Builder filtered($request)
 */
class FACTWM_TRDVERIFY_NON_PO_DETAILS extends Model
{
    const CREATED_AT = 'DCREA';

    const UPDATED_AT = 'DMODI';

    const DELETED_AT = 'DDELETE';

    protected $table = 'FACTWM_TRDVERIFY_NON_PO_DETAILS';

    protected $fillable = [
        'VDESCRIPTION',
        'IQTY',
        'VUOM',
        'IPRICE',
        'IDPP_NILAI_LAIN',
        'IPPN',
        'ITOTAL',
        'TRHVERIFY_NON_PO_IID',

        'VCREA',
        'DCREA',
        'VMODI',
        'DMODI',
        'VDELETE',
        'DDELETE',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(FACTWM_TRHVERIFY_NON_PO::class, 'TRHVERIFY_NON_PO_IID', 'TRHVERIFY_NON_PO_IID');
    }
}
