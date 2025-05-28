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
    Schema::create('prospects', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('phone');
        $table->string('email')->unique();

        // Champs supplémentaires sans 'after'
        $table->json('objet')->nullable();
        $table->json('type')->nullable();
        $table->string('budget')->nullable();
        $table->string('secteur_localisation')->nullable();

        // Index pour améliorer les performances de recherche
        $table->index('secteur_localisation');

        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
{
    Schema::dropIfExists('prospects');
}

};
