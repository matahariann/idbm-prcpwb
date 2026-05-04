<?php

namespace App\Models\FACTWM02;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\FACTWM02\FACTWM_TRHVERIFY_PO;

class FACTWM_LOGVERIFY_PO_OTHER_FILES extends Model
{
    protected $table = 'FACTWM_LOGVERIFY_PO_OTHER_FILES';
    protected $primaryKey = 'IID';

    const CREATED_AT = 'DCREA';

    const UPDATED_AT = 'DMODI';

    const DELETED_AT = 'DDELETE';

    protected $fillable = [
        'TRHVERIFY_PO_IID',
        'VNAME',
        'VPATH',

        'VCREA',
        'DCREA',
        'VMODI',
        'DMODI',
        'VDELETE',
        'DDELETE',
    ];

    public function po(): BelongsTo
    {
        return $this->belongsTo(FACTWM_TRHVERIFY_PO::class, 'TRHVERIFY_PO_IID', 'IID');
    }
}
