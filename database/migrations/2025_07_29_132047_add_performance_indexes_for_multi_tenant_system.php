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
        // Index pour les requêtes tenant-aware sur properties
        Schema::table('properties', function (Blueprint $table) {
            // Index composite pour filtrage par agence et propriétaire
            $table->index(['agency_id', 'owner_id'], 'properties_agency_owner_index');
            // Index pour recherche par type de propriété
            $table->index(['agency_id', 'type'], 'properties_agency_type_index');
            // Index pour recherche par nom (utilisé dans les selects)
            $table->index(['agency_id', 'name'], 'properties_agency_name_index');
        });

        // Index pour les requêtes tenant-aware sur contracts
        Schema::table('contracts', function (Blueprint $table) {
            // Index composite pour filtrage par agence et statut
            $table->index(['agency_id', 'status'], 'contracts_agency_status_index');
            // Index pour recherche par propriété
            $table->index(['agency_id', 'property_id'], 'contracts_agency_property_index');
            // Index pour recherche par locataire
            $table->index(['agency_id', 'tenant_id'], 'contracts_agency_tenant_index');
            // Index pour recherche par dates
            $table->index(['agency_id', 'start_date'], 'contracts_agency_start_date_index');
            $table->index(['agency_id', 'end_date'], 'contracts_agency_end_date_index');
            // Index pour recherche par numéro de contrat
            $table->index(['agency_id', 'contract_number'], 'contracts_agency_number_index');
        });

        // Index pour les requêtes tenant-aware sur payments
        Schema::table('payments', function (Blueprint $table) {
            // Index composite pour filtrage par agence et statut
            $table->index(['agency_id', 'status'], 'payments_agency_status_index');
            // Index pour recherche par contrat
            $table->index(['agency_id', 'contract_id'], 'payments_agency_contract_index');
            // Index pour recherche par locataire
            $table->index(['agency_id', 'tenant_id'], 'payments_agency_tenant_index');
            // Index pour recherche par appartement
            $table->index(['agency_id', 'flat_id'], 'payments_agency_flat_index');
            // Index pour recherche par date de paiement
            $table->index(['agency_id', 'date_payment'], 'payments_agency_date_index');
            // Index pour recherche par type de paiement
            $table->index(['agency_id', 'type'], 'payments_agency_type_index');
            // Index pour recherche par mois
            $table->index(['agency_id', 'current_month'], 'payments_agency_month_index');
        });

        // Index pour les requêtes tenant-aware sur flats
        Schema::table('flats', function (Blueprint $table) {
            // Index pour recherche par propriété (déjà lié à l'agence via property)
            $table->index(['property_id', 'type'], 'flats_property_type_index');
            // Index pour recherche par désignation
            $table->index(['property_id', 'designation'], 'flats_property_designation_index');
        });

        // Index pour les requêtes tenant-aware sur tenants
        Schema::table('tenants', function (Blueprint $table) {
            // Index pour recherche par nom
            $table->index(['agency_id', 'name'], 'tenants_agency_name_index');
            // Index pour recherche par téléphone
            $table->index(['agency_id', 'phone'], 'tenants_agency_phone_index');
        });

        // Index pour les requêtes tenant-aware sur owners
        Schema::table('owners', function (Blueprint $table) {
            // Index pour recherche par nom
            $table->index(['name'], 'owners_name_index');
            // Index pour recherche par téléphone
            $table->index(['phone'], 'owners_phone_index');
        });

        // Index pour la table pivot agency_user (très importante pour l'authentification)
        Schema::table('agency_user', function (Blueprint $table) {
            // Index composite pour les requêtes d'authentification
            $table->index(['user_id', 'agency_id'], 'agency_user_composite_index');
            // Index inverse pour les requêtes d'agence vers utilisateurs
            $table->index(['agency_id', 'user_id'], 'agency_user_inverse_index');
        });

        // Index pour les requêtes sur users (authentification et permissions)
        Schema::table('users', function (Blueprint $table) {
            // Index pour recherche par nom
            $table->index(['name'], 'users_name_index');
        });

        // Index pour model_has_permissions (système de permissions)
        Schema::table('model_has_permissions', function (Blueprint $table) {
            // Index composite pour les vérifications de permissions
            $table->index(['model_type', 'model_id', 'permission_id'], 'model_permissions_composite_index');
        });

        // Index pour model_has_roles (système de rôles)
        Schema::table('model_has_roles', function (Blueprint $table) {
            // Index composite pour les vérifications de rôles
            $table->index(['model_type', 'model_id', 'role_id'], 'model_roles_composite_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer les index dans l'ordre inverse
        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->dropIndex('model_roles_composite_index');
        });

        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->dropIndex('model_permissions_composite_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_name_index');
        });

        Schema::table('agency_user', function (Blueprint $table) {
            $table->dropIndex('agency_user_composite_index');
            $table->dropIndex('agency_user_inverse_index');
        });

        Schema::table('owners', function (Blueprint $table) {
            $table->dropIndex('owners_name_index');
            $table->dropIndex('owners_phone_index');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex('tenants_agency_name_index');
            $table->dropIndex('tenants_agency_phone_index');
        });

        Schema::table('flats', function (Blueprint $table) {
            $table->dropIndex('flats_property_type_index');
            $table->dropIndex('flats_property_designation_index');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_agency_status_index');
            $table->dropIndex('payments_agency_contract_index');
            $table->dropIndex('payments_agency_tenant_index');
            $table->dropIndex('payments_agency_flat_index');
            $table->dropIndex('payments_agency_date_index');
            $table->dropIndex('payments_agency_type_index');
            $table->dropIndex('payments_agency_month_index');
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropIndex('contracts_agency_status_index');
            $table->dropIndex('contracts_agency_property_index');
            $table->dropIndex('contracts_agency_tenant_index');
            $table->dropIndex('contracts_agency_start_date_index');
            $table->dropIndex('contracts_agency_end_date_index');
            $table->dropIndex('contracts_agency_number_index');
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->dropIndex('properties_agency_owner_index');
            $table->dropIndex('properties_agency_type_index');
            $table->dropIndex('properties_agency_name_index');
        });
    }
};
