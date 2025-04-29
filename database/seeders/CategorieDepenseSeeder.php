<?php

namespace Database\Seeders;

use App\Models\CategorieDepense;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorieDepenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        CategorieDepense::query()->delete();
         
        CategorieDepense::factory(12)->create();
    }
}
