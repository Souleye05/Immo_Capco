<?php

namespace Database\Seeders;

use App\Models\Unsold;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UnsoldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Unsold::factory(6)->create();
    }
}
