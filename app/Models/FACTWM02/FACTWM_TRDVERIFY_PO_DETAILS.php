<?php

namespace App\Models\FACTWM02;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\FACTWM02\FACTWM_TRDGR_NOTE_DETAILS;

class FACTWM_TRDVERIFY_PO_DETAILS extends Model
{
    protected $table = 'FACTWM_TRDVERIFY_PO_DETAILS';
    protected $primaryKey = 'IID';

    const CREATED_AT = 'DCREA';

    const UPDATED_AT = 'DMODI';

    const DELETED_AT = 'DDELETE';

    protected $fillable = [
        'TRDGR_NOTE_DETAILS_IID',
        'FACTWM_TRHGR_NOTES_DGR',
        'TRHVERIFY_PO_IID',

        'VCREA',
        'DCREA',
        'VMODI',
        'DMODI',
        'VDELETE',
        'DDELETE',
    ];

    public function gr_details(): BelongsTo
    {
        return $this->belongsTo(FACTWM_TRDGR_NOTE_DETAILS::class, 'TRDGR_NOTE_DETAILS_IID', 'IID');
    }
}
