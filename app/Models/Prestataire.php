<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prestataire extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'nom',
        'profession',
        'phone',
        'adresse',
    ];

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function getFullNameAttribute()
    {
        // return $this->nom . ' ' . $this->profession;
        return "{$this->nom} - {$this->profession}";
    }

}
