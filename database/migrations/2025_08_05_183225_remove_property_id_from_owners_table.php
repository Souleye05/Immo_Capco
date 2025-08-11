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
        Schema::table('owners', function (Blueprint $table) {
            // Supprimer la colonne property_id car un owner peut avoir plusieurs properties
            $table->dropColumn('property_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('owners', function (Blueprint $table) {
            // Restaurer la colonne property_id si rollback
            $table->unsignedBigInteger('property_id')->nullable()->after('user_id');
            $table->foreign('property_id')->references('id')->on('properties')->onDelete('set null');
        });
    }
};
