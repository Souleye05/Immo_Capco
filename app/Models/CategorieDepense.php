<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategorieDepense extends Model
{
    //
    protected $fillable = ['categorie', 'description'];

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}
