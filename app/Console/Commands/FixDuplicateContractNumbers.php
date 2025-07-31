<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Contract;
use App\Services\ContractService;
use Illuminate\Support\Facades\DB;

class FixDuplicateContractNumbers extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'contracts:fix-duplicate-numbers {--dry-run : Show what would be changed without making changes}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Fix duplicate contract numbers by regenerating them';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $isDryRun = $this->option('dry-run');

    if ($isDryRun) {
      $this->info('Running in dry-run mode. No changes will be made.');
    }

    $contractService = app(ContractService::class);
    $totalFixed = 0;

    // 1. Corriger les contrats sans numéro
    $this->fixMissingContractNumbers($contractService, $isDryRun, $totalFixed);

    // 2. Corriger les numéros dupliqués
    $this->fixDuplicateContractNumbers($contractService, $isDryRun, $totalFixed);

    if ($isDryRun) {
      $this->info("Dry run complete. Would fix {$totalFixed} contracts.");
      $this->info("Run without --dry-run to apply changes.");
    } else {
      $this->info("Fixed {$totalFixed} contract issues.");
    }

    return 0;
  }

  private function fixMissingContractNumbers(ContractService $contractService, bool $isDryRun, int &$totalFixed)
  {
    $contractsWithoutNumbers = Contract::whereNull('contract_number')
      ->orWhere('contract_number', '')
      ->get();

    if ($contractsWithoutNumbers->count() > 0) {
      $this->info("Found {$contractsWithoutNumbers->count()} contracts without numbers:");

      foreach ($contractsWithoutNumbers as $contract) {
        if (!$isDryRun) {
          $newNumber = $contractService->generateContractNumber();
          $contract->contract_number = $newNumber;
          $contract->save();

          $this->info("  Fixed: ID {$contract->id} - Assigned number '{$newNumber}'");
        } else {
          $this->info("  Would fix: ID {$contract->id} - Would assign new number");
        }

        $totalFixed++;
      }
    } else {
      $this->info('No contracts without numbers found.');
    }
  }

  private function fixDuplicateContractNumbers(ContractService $contractService, bool $isDryRun, int &$totalFixed)
  {
    // Trouver les numéros de contrats dupliqués
    $duplicates = DB::select("
            SELECT contract_number, COUNT(*) as count 
            FROM contracts 
            WHERE contract_number IS NOT NULL 
            AND contract_number != ''
            GROUP BY contract_number 
            HAVING COUNT(*) > 1
        ");

    if (empty($duplicates)) {
      $this->info('No duplicate contract numbers found.');
      return;
    }

    $this->info('Found ' . count($duplicates) . ' duplicate contract numbers:');

    foreach ($duplicates as $duplicate) {
      $this->warn("Contract number '{$duplicate->contract_number}' appears {$duplicate->count} times");

      // Récupérer tous les contrats avec ce numéro
      $contracts = Contract::where('contract_number', $duplicate->contract_number)
        ->orderBy('created_at')
        ->get();

      // Garder le premier (le plus ancien) et régénérer les autres
      $first = true;
      foreach ($contracts as $contract) {
        if ($first) {
          $this->info("  Keeping original: ID {$contract->id} (created: {$contract->created_at})");
          $first = false;
          continue;
        }

        $oldNumber = $contract->contract_number;

        if (!$isDryRun) {
          $newNumber = $contractService->generateContractNumber();
          $contract->contract_number = $newNumber;
          $contract->save();

          $this->info("  Fixed: ID {$contract->id} - Changed from '{$oldNumber}' to '{$newNumber}'");
        } else {
          $this->info("  Would fix: ID {$contract->id} - Would change from '{$oldNumber}' to new number");
        }

        $totalFixed++;
      }
    }
  }
}
