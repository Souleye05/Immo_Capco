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
        Schema::create('flats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            // $table->foreignId('tenant_id')->nullable()->nullOnDelete();
            $table->foreignId('property_commission_value')->nullable();
            $table->enum('property_commission_unit', ['%', 'F CFA'])->nullable();
            $table->string('reference')->nullable();
            $table->string('designation');
            $table->string('level');
            $table->enum('type', ['chambre','chambre + SDB', 'studio', 'f1', 'f2', 'f3', 'f4', 'f5', 'f6+'])->nullable();
            $table->decimal('loyer',13,0)->nullable();
            $table->decimal('caution',13,0)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flats');
    }
};
