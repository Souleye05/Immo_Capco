<?php

namespace App\Console\Commands;

use App\Http\Middleware\RedirectionMiddleware;
use App\Services\PanelRedirectionService;
use App\Services\RedirectionErrorHandler;
use Illuminate\Console\Command;

class ValidateRedirectionConfig extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'redirection:validate-config
                            {--detailed : Show detailed configuration information}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Validate redirection system configuration and service bindings';

  /**
   * Execute the console command.
   */
  public function handle(): int
  {
    $this->info('🔍 Validating Redirection System Configuration...');
    $this->newLine();

    $allValid = true;

    // Test service container bindings
    $allValid &= $this->validateServiceBindings();
    $this->newLine();

    // Test configuration files
    $allValid &= $this->validateConfiguration();
    $this->newLine();

    // Test environment variables
    $allValid &= $this->validateEnvironmentVariables();
    $this->newLine();

    // Test middleware registration
    $allValid &= $this->validateMiddlewareRegistration();
    $this->newLine();

    if ($allValid) {
      $this->info('✅ All redirection system configurations are valid!');
      return Command::SUCCESS;
    } else {
      $this->error('❌ Some redirection system configurations are invalid. Please check the errors above.');
      return Command::FAILURE;
    }
  }

  /**
   * Validate service container bindings
   */
  private function validateServiceBindings(): bool
  {
    $this->info('📦 Validating Service Container Bindings...');
    $valid = true;

    try {
      // Test PanelRedirectionService
      $service1 = app(PanelRedirectionService::class);
      $service2 = app(PanelRedirectionService::class);

      if ($service1 instanceof PanelRedirectionService && $service1 === $service2) {
        $this->line('  ✓ PanelRedirectionService: Registered as singleton');
      } else {
        $this->error('  ✗ PanelRedirectionService: Not properly registered as singleton');
        $valid = false;
      }

      // Test RedirectionErrorHandler
      $handler1 = app(RedirectionErrorHandler::class);
      $handler2 = app(RedirectionErrorHandler::class);

      if ($handler1 instanceof RedirectionErrorHandler && $handler1 === $handler2) {
        $this->line('  ✓ RedirectionErrorHandler: Registered as singleton');
      } else {
        $this->error('  ✗ RedirectionErrorHandler: Not properly registered as singleton');
        $valid = false;
      }

      // Test RedirectionMiddleware
      $middleware = app(RedirectionMiddleware::class);

      if ($middleware instanceof RedirectionMiddleware) {
        $this->line('  ✓ RedirectionMiddleware: Properly instantiated with dependencies');
      } else {
        $this->error('  ✗ RedirectionMiddleware: Failed to instantiate');
        $valid = false;
      }

      // Test TenantSelectionPage
      $page = app(\App\Filament\Pages\TenantSelectionPage::class);

      if ($page instanceof \App\Filament\Pages\TenantSelectionPage) {
        $this->line('  ✓ TenantSelectionPage: Dependencies can be resolved');
      } else {
        $this->error('  ✗ TenantSelectionPage: Failed to resolve dependencies');
        $valid = false;
      }
    } catch (\Exception $e) {
      $this->error('  ✗ Service binding validation failed: ' . $e->getMessage());
      $valid = false;
    }

    return $valid;
  }

  /**
   * Validate configuration files
   */
  private function validateConfiguration(): bool
  {
    $this->info('⚙️  Validating Configuration Files...');
    $valid = true;

    // Test redirection.php config
    $redirectionConfig = config('redirection');
    if (
      is_array($redirectionConfig) &&
      isset($redirectionConfig['notifications']) &&
      isset($redirectionConfig['logging']) &&
      isset($redirectionConfig['monitoring']) &&
      isset($redirectionConfig['error_messages']) &&
      isset($redirectionConfig['actions'])
    ) {
      $this->line('  ✓ redirection.php: All required sections present');

      if ($this->option('detailed')) {
        $this->line('    - Notifications: ' . count($redirectionConfig['notifications']) . ' settings');
        $this->line('    - Logging: ' . count($redirectionConfig['logging']) . ' settings');
        $this->line('    - Monitoring: ' . count($redirectionConfig['monitoring']) . ' settings');
        $this->line('    - Error Messages: ' . count($redirectionConfig['error_messages']) . ' types');
        $this->line('    - Actions: ' . count($redirectionConfig['actions']) . ' actions');
      }
    } else {
      $this->error('  ✗ redirection.php: Missing required configuration sections');
      $valid = false;
    }

    // Test services.php config
    $servicesConfig = config('services.redirection');
    if (
      is_array($servicesConfig) &&
      isset($servicesConfig['panel_redirection_service']) &&
      isset($servicesConfig['error_handler']) &&
      isset($servicesConfig['middleware'])
    ) {
      $this->line('  ✓ services.php: Redirection services configuration present');
    } else {
      $this->error('  ✗ services.php: Missing redirection services configuration');
      $valid = false;
    }

    return $valid;
  }

  /**
   * Validate environment variables
   */
  private function validateEnvironmentVariables(): bool
  {
    $this->info('🌍 Validating Environment Variables...');
    $valid = true;

    $requiredEnvVars = [
      'REDIRECTION_LOGGING_ENABLED' => 'boolean',
      'REDIRECTION_LOG_STACK_TRACE' => 'boolean',
      'REDIRECTION_LOG_REQUEST_DATA' => 'boolean',
      'REDIRECTION_MONITORING_ENABLED' => 'boolean',
      'REDIRECTION_ALERT_THRESHOLD' => 'integer',
      'ADMIN_EMAIL' => 'email',
      'SUPPORT_EMAIL' => 'email',
      'HELP_URL' => 'string',
    ];

    foreach ($requiredEnvVars as $envVar => $type) {
      $value = env($envVar);

      if ($value !== null) {
        $this->line("  ✓ {$envVar}: {$value}");

        if ($this->option('detailed')) {
          $this->validateEnvVarType($envVar, $value, $type);
        }
      } else {
        $this->error("  ✗ {$envVar}: Not set");
        $valid = false;
      }
    }

    return $valid;
  }

  /**
   * Validate environment variable type
   */
  private function validateEnvVarType(string $envVar, $value, string $expectedType): void
  {
    switch ($expectedType) {
      case 'boolean':
        if (!in_array(strtolower($value), ['true', 'false', '1', '0'])) {
          $this->warn("    ⚠ {$envVar}: Expected boolean value");
        }
        break;
      case 'integer':
        if (!is_numeric($value)) {
          $this->warn("    ⚠ {$envVar}: Expected integer value");
        }
        break;
      case 'email':
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
          $this->warn("    ⚠ {$envVar}: Expected valid email address");
        }
        break;
    }
  }

  /**
   * Validate middleware registration
   */
  private function validateMiddlewareRegistration(): bool
  {
    $this->info('🛡️  Validating Middleware Registration...');
    $valid = true;

    try {
      $router = app('router');
      $middlewareGroups = $router->getMiddleware();

      if (
        isset($middlewareGroups['panel.redirect']) &&
        $middlewareGroups['panel.redirect'] === RedirectionMiddleware::class
      ) {
        $this->line('  ✓ panel.redirect: Middleware alias registered');
      } else {
        $this->error('  ✗ panel.redirect: Middleware alias not registered');
        $valid = false;
      }

      // Check if RedirectionPanelProvider is registered
      $providers = app()->getLoadedProviders();
      if (isset($providers[\App\Providers\RedirectionServiceProvider::class])) {
        $this->line('  ✓ RedirectionServiceProvider: Registered and loaded');
      } else {
        $this->error('  ✗ RedirectionServiceProvider: Not registered');
        $valid = false;
      }
    } catch (\Exception $e) {
      $this->error('  ✗ Middleware validation failed: ' . $e->getMessage());
      $valid = false;
    }

    return $valid;
  }
}
