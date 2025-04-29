<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategorieDepense extends Model
{
    //
    use HasFactory;
    protected $fillable = ['categorie', 'description'];

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}
