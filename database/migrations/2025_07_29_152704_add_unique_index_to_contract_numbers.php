<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Vérifier si l'index existe déjà
        $indexes = DB::select("SHOW INDEX FROM contracts WHERE Key_name = 'contracts_contract_number_unique'");

        if (empty($indexes)) {
            Schema::table('contracts', function (Blueprint $table) {
                // Ajouter un index unique sur contract_number s'il n'existe pas déjà
                $table->unique('contract_number', 'contracts_contract_number_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            // Supprimer l'index unique
            $table->dropUnique('contracts_contract_number_unique');
        });
    }
};
