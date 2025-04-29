<?php

namespace Database\Seeders;

use App\Models\VersementImp;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VersementImpSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        VersementImp::factory(6)->create();
    }
}
