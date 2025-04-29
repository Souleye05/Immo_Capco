<?php

namespace Database\Seeders;

use App\Models\Remittance;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RemittanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Remittance::factory(6)->create();
    }
}
