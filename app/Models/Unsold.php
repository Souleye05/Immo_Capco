<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Unsold extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'amount',
        'motif',
        'reference',
        'status',
        'date',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function versementimp()
    {
        return $this->hasMany(versementimp::class);
    }
}
