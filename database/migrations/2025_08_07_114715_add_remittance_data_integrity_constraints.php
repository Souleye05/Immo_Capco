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
        Schema::table('remittances', function (Blueprint $table) {
            // Add foreign key constraints to ensure referential integrity
            $table->foreign('owner_id')->references('id')->on('owners')->onDelete('restrict');
            $table->foreign('property_id')->references('id')->on('properties')->onDelete('restrict');

            // Add check constraints for data validation
            $table->check('amount >= 0', 'remittances_amount_positive');
            $table->check('amount_to_transfer >= 0', 'remittances_amount_to_transfer_positive');
            $table->check('remaining >= 0', 'remittances_remaining_positive');

            // Add check constraint for valid remittance types
            $table->check("remittance_type IN ('loyer', 'caution')", 'remittances_valid_type');

            // Add check constraint for valid status values
            $table->check("status IN ('Pending', 'Partial', 'Paid')", 'remittances_valid_status');

            // Add check constraint for valid months (1-12) when remittance_type is 'loyer'
            $table->check("(remittance_type != 'loyer' OR (current_month >= 1 AND current_month <= 12))", 'remittances_valid_month');

            // Add check constraint for valid years when remittance_type is 'loyer'
            $table->check("(remittance_type != 'loyer' OR (current_year >= 2020 AND current_year <= 2030))", 'remittances_valid_year');

            // Add unique constraint to prevent duplicate rent remittances for same property and period
            $table->unique(['property_id', 'remittance_type', 'current_month', 'current_year'], 'remittances_unique_rent_period');
        });

        Schema::table('owners', function (Blueprint $table) {
            // Add foreign key constraint for user association
            if (Schema::hasColumn('owners', 'user_id')) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            }

            // Add check constraint for name length
            $table->check("LENGTH(name) > 0 AND LENGTH(name) <= 255", 'owners_valid_name_length');

            // Add check constraint for phone format if provided (simplified for database compatibility)
            $table->check("phone IS NULL OR LENGTH(phone) BETWEEN 8 AND 20", 'owners_valid_phone_format');
        });

        Schema::table('properties', function (Blueprint $table) {
            // Add foreign key constraints
            $table->foreign('agency_id')->references('id')->on('agencies')->onDelete('restrict');
            $table->foreign('owner_id')->references('id')->on('owners')->onDelete('restrict');

            // Add check constraints for commission settings
            $table->check('commission_value > 0', 'properties_commission_value_positive');
            $table->check("commission_unit IN ('percentage', 'fixed')", 'properties_valid_commission_unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('remittances', function (Blueprint $table) {
            // Drop foreign key constraints
            $table->dropForeign(['owner_id']);
            $table->dropForeign(['property_id']);

            // Drop check constraints
            $table->dropCheckConstraint('remittances_amount_positive');
            $table->dropCheckConstraint('remittances_amount_to_transfer_positive');
            $table->dropCheckConstraint('remittances_remaining_positive');
            $table->dropCheckConstraint('remittances_valid_type');
            $table->dropCheckConstraint('remittances_valid_status');
            $table->dropCheckConstraint('remittances_valid_month');
            $table->dropCheckConstraint('remittances_valid_year');

            // Drop unique constraint
            $table->dropUnique('remittances_unique_rent_period');
        });

        Schema::table('owners', function (Blueprint $table) {
            // Drop foreign key constraint
            if (Schema::hasColumn('owners', 'user_id')) {
                $table->dropForeign(['user_id']);
            }

            // Drop check constraints
            $table->dropCheckConstraint('owners_valid_name_length');
            $table->dropCheckConstraint('owners_valid_phone_format');
        });

        Schema::table('properties', function (Blueprint $table) {
            // Drop foreign key constraints
            $table->dropForeign(['agency_id']);
            $table->dropForeign(['owner_id']);

            // Drop check constraints
            $table->dropCheckConstraint('properties_commission_value_positive');
            $table->dropCheckConstraint('properties_valid_commission_unit');
        });
    }
};
