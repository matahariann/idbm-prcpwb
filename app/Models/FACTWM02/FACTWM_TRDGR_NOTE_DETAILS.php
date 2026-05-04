<?php

namespace App\Models\FACTWM02;

use App\Models\BaseModel;

/**
 * @method static \Illuminate\Database\Eloquent\Builder filtered($request)
 */
class FACTWM_TRDGR_NOTE_DETAILS extends BaseModel
{
    protected $table = 'FACTWM_TRDGR_NOTE_DETAILS';

    protected $primaryKey = 'IID';

    const CREATED_AT = 'DCREA';

    const UPDATED_AT = 'DMODI';

    const DELETED_AT = 'DDELETE';

    protected $fillable = [
        'IID_GR_NOTE',
        'VGR_NUMBER',
        'VMATERIAL_CODE',
        'VDESCRIPTION',
        'IQTY',
        'UOM',
        'VPRICE',
        'VAMOUNT',
        'VOBJ_STATE',
        'VCURRENCY',
        'DGR',
        'DSYNC',
        'DAPPROVE',
        'DDISPUTE',
        'VORDER_NO',
        'VLINE_NO',
        'VRELEASE_NO',
        'VCREA',
        'DCREA',
        'VMODI',
        'DMODI',
        'VDELETE',
        'DDELETE',
        'IRECEIPT_NO',
        'VRECEIPT_SEQUENCE',
    ];
}
