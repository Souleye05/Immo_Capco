<?php

use App\Enums\PaymentType;
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
            // Ajouter la colonne type après numero
            $table->enum('type', array_column(PaymentType::cases(), 'value'))
                  ->default(PaymentType::LOYER->value);
            
            // Ajouter un index pour optimiser les requêtes
            $table->index(['tenant_id', 'flat_id', 'type']);
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('current_month')->nullable();
            $table->decimal('amount',13,0)->nullable();
            $table->boolean('status')->nullable();
            $table->date('date_payment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::dropIfExists('payments');
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'flat_id', 'type']);
            $table->dropColumn('type');
        });
    }
};
