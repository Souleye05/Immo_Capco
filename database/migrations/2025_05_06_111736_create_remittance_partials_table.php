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
        Schema::create('remittance_partials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('remittance_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->nullable();
            $table->decimal('amount', 15, 2);
            $table->enum('mode_remit', ['OM', 'Wave', 'Free Money', 'Chèque', 'Virement', 'Espèces'])->nullable();
            $table->string('receipt_path')->nullable();
            $table->string('current_month')->nullable();
            $table->date('remittance_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remittance_partials');
    }
};
