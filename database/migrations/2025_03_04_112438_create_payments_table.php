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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flat_id');
            $table->string('numero')->nullable();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('current_month')->nullable();
            $table->decimal('amount',13,0)->nullable();
            $table->boolean('status')->nullable();
            $table->date('date_payment')->nullable();
            // $table->enum('payment_method', ['OM', 'Wave', 'Free Money', 'Chèque', 'Virement', 'Espèces'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
