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
        Schema::create('user_agency_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('agency_role_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_by')->constrained('users'); // Agency-owner qui a assigné
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();

            // Un utilisateur ne peut avoir le même rôle qu'une fois dans une agence
            $table->unique(['user_id', 'agency_role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_agency_roles');
    }
};
