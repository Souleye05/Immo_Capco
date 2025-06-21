<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractsTable extends Migration
{
    public function up()
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('flat_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('property_id')->constrained()->onDelete('cascade');

            
            // Informations contractuelles
            $table->string('contract_number')->unique(); // Numéro contrat unique
            $table->decimal('monthly_rent', 10, 2); // Loyer mensuel
            $table->decimal('cautions', 10, 2)->nullable(); // Dépôt de garantie
            
            // Dates
            $table->date('start_date'); // Date début contrat
            $table->date('end_date'); // Date fin contrat
            $table->integer('contract_duration_months')->default(12); // Durée contrat en mois
            
            // Conditions
            $table->integer('notice_period_days')->default(30); // Préavis en jours
            $table->integer('alert_days_before')->default(90); // Alerte X jours avant fin
            $table->boolean('auto_renewal')->default(false); // Renouvellement auto
            $table->integer('renewal_duration_months')->nullable(); // Durée renouvellement
            
            // Statuts
            $table->enum('status', [
                'draft',      // Brouillon
                'active',     // Actif
                'expired',    // Expiré
                'terminated', // Résilié
                'renewed'     // Renouvelé
            ])->default('draft');
            
            // Métadonnées
            $table->text('notes')->nullable(); // Notes additionnelles
            $table->json('conditions')->nullable(); // Conditions spéciales (JSON)
            
            $table->timestamps();
            
            // Index pour optimisation
            $table->index(['flat_id', 'status']);
            $table->index(['end_date', 'status']);
            $table->index('contract_number');
        });
    }

    public function down()
    {
        Schema::dropIfExists('contracts');
    }
}
