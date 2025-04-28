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
    Schema::create('prestataires', function (Blueprint $table) {
        $table->id();
        $table->string('nom');
        $table->string('profession')->nullable(); // Plombier, électricien, etc.
        $table->string('phone')->nullable();
        $table->string('adresse')->nullable();
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down()
{
    Schema::table('expenses', function (Blueprint $table) {
        $table->dropForeign(['prestataire_id']); // supprimer la foreign key d'abord
    });

    Schema::dropIfExists('prestataires');
}

};
