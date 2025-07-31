<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, ensure we have at least one agency
        $defaultAgency = \App\Models\Agency::first();

        if (!$defaultAgency) {
            // Create a default agency if none exists
            $defaultAgency = \App\Models\Agency::create([
                'name' => 'Agence par défaut',
                'slug' => 'agence-par-defaut'
            ]);
        }

        // Update properties without agency_id
        \DB::table('properties')
            ->whereNull('agency_id')
            ->update(['agency_id' => $defaultAgency->id]);

        // Update contracts without agency_id (if any exist)
        \DB::table('contracts')
            ->whereNull('agency_id')
            ->update(['agency_id' => $defaultAgency->id]);

        // Update payments without agency_id by getting agency_id from their contract
        \DB::statement('
            UPDATE payments p
            INNER JOIN contracts c ON p.contract_id = c.id
            SET p.agency_id = c.agency_id
            WHERE p.agency_id IS NULL
        ');

        // Handle orphaned payments (payments without valid contracts)
        \DB::table('payments')
            ->whereNull('agency_id')
            ->update(['agency_id' => $defaultAgency->id]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset agency_id to null for all records
        \DB::table('properties')->update(['agency_id' => null]);
        \DB::table('contracts')->update(['agency_id' => null]);
        \DB::table('payments')->update(['agency_id' => null]);
    }
};
