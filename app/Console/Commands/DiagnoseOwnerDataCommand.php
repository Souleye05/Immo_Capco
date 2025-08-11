<?php

namespace App\Console\Commands;

use App\Services\OwnerUserService;
use Illuminate\Console\Command;

class DiagnoseOwnerDataCommand extends Command
{
  /**
   * The name and signature of the console command.
   */
  protected $signature = 'owner:diagnose-data {--fix : Automatically fix identified issues}';

  /**
   * The console command description.
   */
  protected $description = 'Diagnose and optionally fix owner-user relationship issues';

  /**
   * The OwnerUserService instance.
   */
  protected OwnerUserService $ownerUserService;

  /**
   * Create a new command instance.
   */
  public function __construct(OwnerUserService $ownerUserService)
  {
    parent::__construct();
    $this->ownerUserService = $ownerUserService;
  }

  /**
   * Execute the console command.
   */
  public function handle(): int
  {
    $this->info('🔍 Diagnosing owner-user relationship issues...');
    $this->newLine();

    // Run diagnosis
    $issues = $this->ownerUserService->diagnoseRelations();

    // Display results
    $this->displayDiagnosisResults($issues);

    // Check if we should fix issues
    if ($this->option('fix')) {
      $this->newLine();
      if ($this->hasIssues($issues)) {
        if ($this->confirm('Do you want to proceed with automatic repairs?')) {
          $this->performRepairs();
        } else {
          $this->warn('Repair cancelled by user.');
          return Command::SUCCESS;
        }
      } else {
        $this->info('✅ No issues found to fix.');
      }
    } else {
      $this->newLine();
      if ($this->hasIssues($issues)) {
        $this->info('💡 To automatically fix these issues, run: php artisan owner:diagnose-data --fix');
      }
    }

    return Command::SUCCESS;
  }

  /**
   * Display the diagnosis results in a formatted way.
   */
  private function displayDiagnosisResults(array $issues): void
  {
    // Owners without user_id
    if (!empty($issues['owners_without_user_id'])) {
      $this->error('❌ Owners without user_id: ' . count($issues['owners_without_user_id']));
      $headers = ['Owner ID', 'Name', 'Email', 'Properties Count'];
      $rows = array_map(function ($issue) {
        return [
          $issue['owner_id'],
          $issue['owner_name'],
          $issue['owner_email'] ?? 'N/A',
          $issue['properties_count'],
        ];
      }, $issues['owners_without_user_id']);
      $this->table($headers, $rows);
      $this->newLine();
    } else {
      $this->info('✅ All owners have user_id assigned');
    }

    // Owners with invalid user_id
    if (!empty($issues['owners_with_invalid_user_id'])) {
      $this->error('❌ Owners with invalid user_id: ' . count($issues['owners_with_invalid_user_id']));
      $headers = ['Owner ID', 'Name', 'Invalid User ID'];
      $rows = array_map(function ($issue) {
        return [
          $issue['owner_id'],
          $issue['owner_name'],
          $issue['invalid_user_id'],
        ];
      }, $issues['owners_with_invalid_user_id']);
      $this->table($headers, $rows);
      $this->newLine();
    } else {
      $this->info('✅ All owner user_id references are valid');
    }

    // Agency mismatches
    if (!empty($issues['agency_mismatches'])) {
      $this->warn('⚠️  Agency assignment mismatches: ' . count($issues['agency_mismatches']));
      $headers = ['Owner ID', 'Name', 'User Agencies', 'Property Agency', 'Property Name'];
      $rows = array_map(function ($issue) {
        return [
          $issue['owner_id'],
          $issue['owner_name'],
          implode(', ', $issue['user_agencies']),
          $issue['property_agency'],
          $issue['property_name'],
        ];
      }, $issues['agency_mismatches']);
      $this->table($headers, $rows);
      $this->newLine();
    } else {
      $this->info('✅ All agency assignments are consistent');
    }

    // Users without owner role
    if (!empty($issues['users_without_owner_role'])) {
      $this->warn('⚠️  Users without owner role: ' . count($issues['users_without_owner_role']));
      $headers = ['User ID', 'Name', 'Email', 'Owners Count'];
      $rows = array_map(function ($issue) {
        return [
          $issue['user_id'],
          $issue['user_name'],
          $issue['user_email'],
          $issue['owners_count'],
        ];
      }, $issues['users_without_owner_role']);
      $this->table($headers, $rows);
      $this->newLine();
    } else {
      $this->info('✅ All users with owners have the owner role');
    }
  }

  /**
   * Check if there are any issues to fix.
   */
  private function hasIssues(array $issues): bool
  {
    return !empty($issues['owners_without_user_id']) ||
      !empty($issues['owners_with_invalid_user_id']) ||
      !empty($issues['agency_mismatches']) ||
      !empty($issues['users_without_owner_role']);
  }

  /**
   * Perform automatic repairs.
   */
  private function performRepairs(): void
  {
    $this->info('🔧 Starting automatic repairs...');
    $this->newLine();

    $results = $this->ownerUserService->repairRelations();

    // Display repair results
    $this->displayRepairResults($results);

    // Run diagnosis again to verify fixes
    $this->newLine();
    $this->info('🔍 Running post-repair diagnosis...');
    $postRepairIssues = $this->ownerUserService->diagnoseRelations();

    if ($this->hasIssues($postRepairIssues)) {
      $this->warn('⚠️  Some issues remain after repair:');
      $this->displayDiagnosisResults($postRepairIssues);
    } else {
      $this->info('🎉 All issues have been successfully resolved!');
    }
  }

  /**
   * Display the repair results.
   */
  private function displayRepairResults(array $results): void
  {
    if (!empty($results['repaired_owners'])) {
      $this->info('✅ Repaired owners: ' . count($results['repaired_owners']));
      $headers = ['Owner ID', 'Owner Name', 'Matched User ID', 'User Email'];
      $rows = array_map(function ($result) {
        return [
          $result['owner_id'],
          $result['owner_name'],
          $result['matched_user_id'],
          $result['matched_user_email'],
        ];
      }, $results['repaired_owners']);
      $this->table($headers, $rows);
    }

    if (!empty($results['created_users'])) {
      $this->info('✅ Created new users: ' . count($results['created_users']));
      $headers = ['Owner ID', 'Created User ID', 'User Email'];
      $rows = array_map(function ($result) {
        return [
          $result['owner_id'],
          $result['created_user_id'],
          $result['user_email'],
        ];
      }, $results['created_users']);
      $this->table($headers, $rows);
    }

    if (!empty($results['fixed_agency_assignments'])) {
      $this->info('✅ Fixed agency assignments: ' . count($results['fixed_agency_assignments']));
    }

    if (!empty($results['assigned_roles'])) {
      $this->info('✅ Assigned owner roles: ' . count($results['assigned_roles']));
    }

    if (!empty($results['errors'])) {
      $this->error('❌ Errors encountered: ' . count($results['errors']));
      foreach ($results['errors'] as $error) {
        $this->error('  - ' . ($error['owner_id'] ?? $error['user_id'] ?? 'Unknown') . ': ' . $error['error']);
      }
    }
  }
}
