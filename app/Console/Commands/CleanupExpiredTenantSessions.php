<?php

namespace App\Console\Commands;

use App\Services\TenantSessionManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupExpiredTenantSessions extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'tenant-sessions:cleanup
                            {--force : Force cleanup without confirmation}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Clean up expired tenant sessions from the system';

  public function __construct(
    private TenantSessionManager $tenantSessionManager
  ) {
    parent::__construct();
  }

  /**
   * Execute the console command.
   */
  public function handle(): int
  {
    $this->info('Starting tenant session cleanup...');

    try {
      // Clean up expired sessions
      $this->tenantSessionManager->cleanupExpiredSessions();

      $this->info('✅ Tenant session cleanup completed successfully.');
      Log::info('Scheduled tenant session cleanup completed');

      return Command::SUCCESS;
    } catch (\Exception $e) {
      $this->error("❌ Failed to cleanup tenant sessions: {$e->getMessage()}");
      Log::error("Failed to cleanup tenant sessions: {$e->getMessage()}");

      return Command::FAILURE;
    }
  }
}
