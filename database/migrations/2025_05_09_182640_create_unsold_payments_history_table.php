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
        Schema::create('unsold_payments_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants');
            $table->foreignId('payment_id')->constrained('payments');
            $table->decimal('total_amount', 12, 2);
            $table->json('details')->nullable();
            $table->timestamp('cleared_at');
            $table->timestamps();
        });

        // Ajouter la colonne paid_at à la table unsolds si elle n'existe pas déjà
        if (!Schema::hasColumn('unsolds', 'paid_at')) {
            Schema::table('unsolds', function (Blueprint $table) {
                $table->timestamp('paid_at')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unsold_payments_history');
        
        if (Schema::hasColumn('unsolds', 'paid_at')) {
            Schema::table('unsolds', function (Blueprint $table) {
                $table->dropColumn('paid_at');
            });
        }
    }
};
