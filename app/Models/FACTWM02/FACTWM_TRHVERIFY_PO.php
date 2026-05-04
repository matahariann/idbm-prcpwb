<?php

namespace App\Models\FACTWM02;

// use App\Models\BaseModel;
use App\Policies\VerifyPoPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\FACTWM02\FACTWM_TRDVERIFY_PO_DETAILS;
use App\Models\FACTWM01\FACTWM_MSHSUPPLIER;

#[UsePolicy(VerifyPoPolicy::class)]
class FACTWM_TRHVERIFY_PO extends Model
{
    protected $table = 'FACTWM_TRHVERIFY_PO';
    protected $primaryKey = 'IID';

    const CREATED_AT = 'DCREA';

    const UPDATED_AT = 'DMODI';

    const DELETED_AT = 'DDELETE';

    protected $fillable = [
        'VSUPPLIER_CODE',
        'VINVOICE_NUMBER',
        'VBILLING_STATEMENT',
        'VUNIQUE_CODE',
        'VGRN_NUMBER',
        'DINVOICE_DATE',
        'VTAX_INVOICE_NUMBER',
        'DTAX_INVOICE_DATE',
        'ITOTAL',
        'IPPN',
        'IDPP',
        'INET_AMOUNT',
        'VNPWP_SUPPLIER',
        'VINVOICE_FILE',
        'VTAX_INVOICE_FILE',
        'VREKAP_JASA_FILE',
        'VGR_NUMBER_IID',
        'VPPH',
        'VOBJECT',
        'IDPP_PPH',
        'FTARRIF',
        'FVALUE',
        'VCREA',
        'DCREA',
        'VMODI',
        'DMODI',
        'VDELETE',
        'DDELETE',
        'VNOTES',
        'DAPPROVED',
        'VPYHSICAL_DOC_STATUS',
        'DSUBMITTED',
        'VSTATUS_INVOICE',
        'VSTATUS',
        'VREQUIRE_MATERAI_OCR',
        'VOCR_MATERAI_STATUS',
    ];

    protected $casts = [
        'VGR_NUMBER_IID' => 'array',
        'DINVOICE_DATE' => 'date',
        'DTAX_INVOICE_DATE' => 'date',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(FACTWM_TRDVERIFY_PO_DETAILS::class, 'TRHVERIFY_PO_IID', 'IID');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(FACTWM_MSHSUPPLIER::class, 'VSUPPLIER_CODE', 'VSUPPLIER_CODE');
    }
}
