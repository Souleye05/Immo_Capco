<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Appeler tous les seeders nécessaires
        $this->call([
            UserSeeder::class,
            CategorieDepenseSeeder::class,
            PrestataireSeeder::class,
            ExpenseSeeder::class,
            OwnerSeeder::class,
            PropertySeeder::class,
            FlatSeeder::class,
            ProspectSeeder::class,
            RemittanceSeeder::class,
            TenantSeeder::class,
            PaymentSeeder::class,
            UnsoldSeeder::class,
            VersementSeeder::class,
            VersementImpSeeder::class,
        ]);
    }
}
