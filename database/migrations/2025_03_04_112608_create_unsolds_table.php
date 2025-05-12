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
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tenant_id')->nullable()->nullOnDelete();
            $table->boolean('is_archived')->default(false);
            $table->string('month')->nullable(); // Exemple : "2024-05"
            $table->string('reference')->nullable();
            $table->decimal('amount',13,0)->nullable();
            $table->text('motif')->nullable();
            $table->boolean('status')->nullable();
            $table->string('date')->nullable();
            // $table->softDeletes(); // Pour gérer la suppression douce
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::table('unsolds', function (Blueprint $table) {
        //     $table->dropSoftDeletes();
        // });
        Schema::dropIfExists('unsolds');
    }
};
