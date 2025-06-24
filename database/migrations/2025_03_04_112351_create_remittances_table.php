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
        Schema::create('remittances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->nullOnDelete();
            $table->foreignId('property_id')->nullable()->nullOnDelete();
            $table->string('numero')->nullable();
            $table->enum('status', ['Pending', 'Partial', 'Paid'])->default('Pending');
            $table->string('remittance_type');
            $table->enum('mode_remit', ['OM', 'Wave', 'Free Money', 'Chèque', 'Virement', 'Espèces'])->nullable();
            $table->string('current_month')->nullable();
            $table->integer('current_year')->nullable();
            $table->decimal('amount',15,0)->nullable();
            $table->decimal('amount_to_transfer',15,0)->nullable();
            $table->decimal('remaining',15,0)->nullable();
            $table->date('remittance_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remittances');
    }
};
