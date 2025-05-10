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
        Schema::create('versements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('reference')->nullable();
            $table->decimal('amount',13,0)->nullable();
            // $table->date('current_month')->nullable();
            $table->enum('payment_method', ['OM', 'Wave', 'Free Money', 'Chèque', 'Virement', 'Espèces'])->nullable();
            $table->date('versement_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('versements');
    }
};
