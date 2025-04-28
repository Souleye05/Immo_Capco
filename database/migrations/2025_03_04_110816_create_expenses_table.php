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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->nullable()->cascadeOnDelete();
            $table->foreignId('flat_id')->nullable()->cascadeOnDelete();
            $table->foreignId('prestataire_id')->nullable()->constrained()->nullOnDelete();
            $table->string('titre')->nullable();
            $table->string('type')->nullable();
            $table->text('libelle')->nullable();
            $table->decimal('amount',13,0)->nullable();
            $table->date('payment_date')->nullable();
            $table->enum('payment_method', ['OM', 'Wave', 'Free Money', 'Chèque', 'Virement', 'Espèces'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
