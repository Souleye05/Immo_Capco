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
        Schema::table('contracts', function (Blueprint $table) {
            // Supprimer l'index unique sur contract_number
            $table->dropUnique('contracts_contract_number_unique');

            // Ajouter un index unique composite sur (contract_number, agency_id)
            $table->unique(['contract_number', 'agency_id'], 'contracts_number_agency_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            // Supprimer l'index unique composite
            $table->dropUnique('contracts_number_agency_unique');

            // Remettre l'index unique sur contract_number
            $table->unique('contract_number', 'contracts_contract_number_unique');
        });
    }
};
