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
        Schema::create('agency_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Nom du rôle (ex: "Manager Commercial", "Assistant Comptable")
            $table->string('slug'); // Slug unique dans l'agence
            $table->text('description')->nullable();
            $table->json('permissions')->nullable(); // Permissions spécifiques à ce rôle
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users'); // Agency-owner qui a créé ce rôle
            $table->timestamps();

            // Un rôle doit être unique par agence
            $table->unique(['agency_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_roles');
    }
};
