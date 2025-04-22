<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VersementImp extends Model
{
    use HasFactory;

    protected $fillable = [
        'unsold_id',
        'reference',
        'amount',
        'current_month',
        'versement_date',
    ];

    public function unsold()
    {
        return $this->belongsTo(Unsold::class);
    }
}
