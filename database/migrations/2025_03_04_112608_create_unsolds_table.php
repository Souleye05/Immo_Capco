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
        Schema::create('unsolds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->nullOnDelete();
            $table->string('reference')->nullable();
            $table->decimal('amount',13,0)->nullable();
            $table->text('motif')->nullable();
            $table->boolean('etat')->nullable();
            $table->string('date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unsolds');
    }
};
