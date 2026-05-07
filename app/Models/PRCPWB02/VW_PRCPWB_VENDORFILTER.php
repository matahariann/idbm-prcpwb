<?php

namespace App\Models\PRCPWB02;

use Illuminate\Database\Eloquent\Model;

class VW_PRCPWB_VENDORFILTER extends Model
{
    protected $connection = 'prcpwb';
    protected $table = 'vw_prcpwb_vendorfilter';
    protected $primaryKey = null;
    
    public $incrementing = false;
    public $timestamps = false;

    public function getTable(): string
    {
        return $this->table;
    }
}
