<?php

namespace App\Models\FACTWM02;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @method static \Illuminate\Database\Eloquent\Builder filtered($request)
 */
class FACTWM_TRHGR_NOTES extends BaseModel
{
    protected $table = 'FACTWM_TRHGR_NOTES';

    protected $primaryKey = 'IID';

    const CREATED_AT = 'DCREA';

    const UPDATED_AT = 'DMODI';

    const DELETED_AT = 'DDELETE';

    protected $fillable = [
        'VREF_TYPE',
        'VRECEIPT_SEQUENCE',
        'IRECEIPT_NO',
        'DDELIVERY_DATE',
        'DAPPROVAL_DATE',
        'VNOTEID',
        'VGR_NUMBER',
        'VDELIVERY_NUMBER',
        'VPO_NUMBER',
        'VVENDOR_CODE',
        'VVENDOR_NAME',
        'VSTATUS',
        'VSTATUS_SUBMITTED',
        'VDISPUTEFILE',
        'VDISPUTEDESC',
        'VDISPUTEREJECTDESC',
        'DGR',
        'DSYNC',
        'DAPPROVE',
        'DDISPUTE',
        'VSOURCEREF4',
        'VCONTRACTNO',
        'VRETURN_REF',
        'VCREA',
        'DCREA',
        'VMODI',
        'DMODI',
        'VDELETE',
        'DDELETE',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(FACTWM_TRDGR_NOTE_DETAILS::class, 'IID_GR_NOTE', 'IID');
    }

    public function returnGr(): HasOne
    {
        return $this->hasOne(FACTWM_TRHGR_NOTES::class, 'VRETURN_REF', 'VGR_NUMBER');
    }

    public function returnGrs(): HasMany
    {
        return $this->hasMany(FACTWM_TRHGR_NOTES::class, 'VRETURN_REF', 'VGR_NUMBER');
    }

    public function returnGRRef(): BelongsTo
    {
        return $this->belongsTo(FACTWM_TRHGR_NOTES::class, 'VRETURN_REF', 'VGR_NUMBER');
    }
}
