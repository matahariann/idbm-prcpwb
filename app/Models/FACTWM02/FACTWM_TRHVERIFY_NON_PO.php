<?php

namespace App\Models\FACTWM02;

use Illuminate\Database\Eloquent\Model;
use App\Policies\VerifyNonPoPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\FACTWM01\FACTWM_MSHSUPPLIER;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @method static \Illuminate\Database\Eloquent\Builder filtered($request)
 */
#[UsePolicy(VerifyNonPoPolicy::class)]
class FACTWM_TRHVERIFY_NON_PO extends Model
{
    protected $table = 'FACTWM_TRHVERIFY_NON_PO';
    protected $primaryKey = 'IID';

    const CREATED_AT = 'DCREA';

    const UPDATED_AT = 'DMODI';

    const DELETED_AT = 'DDELETE';
    protected $fillable = [
        'VSUPPLIER_CODE',
        'VBILLING_STATEMENT',
        'VUNIQUE_CODE',
        'VINV_NO_SUPPLIER',
        'DINV_DATE',
        'IDPP_PPH',
        'VPPH',
        'VDPP',
        'VPPN',
        'VTAX_CODE',
        'VTAX_NUMBER',
        'DTAX_DATE',
        'INET_AMOUNT',
        'ITOTAL',
        'VSTATUS',
        'VPDF_TAX',
        'VPDF_INVOICE',
        'VQRCODE',
        'DSUBMITTED',
        // 'DAPPROVED',
        'DPLAN_PAY_DATE',
        'VCREA',
        'DCREA',
        'VMODI',
        'DMODI',
        'VDELETE',
        'DDELETE',
        'VNOTES',
        'VOBJECT',
        'FTARRIF',
        'FVALUE',
        'DAPPROVED',
        'VPYHSICAL_DOC_STATUS',
        'DSUBMITTED',
        'VSTATUS_INVOICE',
    ];

    protected $casts = [
        'DINV_DATE' => 'datetime',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(FACTWM_TRDVERIFY_NON_PO_DETAILS::class, 'TRHVERIFY_NON_PO_IID', 'IID');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(FACTWM_MSHSUPPLIER::class, 'VSUPPLIER_CODE', 'VSUPPLIER_CODE');
    }
}
