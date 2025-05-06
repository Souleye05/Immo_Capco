<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RemittancePartial extends Model
{
    //
    protected $fillable = [
        'remittance_id',
        'reference',
        'amount',
        'mode_remit',
        'current_month',
        'remittance_date',
    ];
    
    public function remittance()
{
    return $this->belongsTo(Remittance::class);
}

}
