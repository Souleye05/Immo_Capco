<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
  /**
   * Run the database seeder.
   */
  public function run(): void
  {
    $settings = [
      // General Settings
      [
        'key' => 'app_name',
        'value' => 'Immo Management Platform',
        'type' => 'string',
        'description' => 'The name of the application displayed in the interface',
        'group' => 'general',
        'is_public' => true,
      ],
      [
        'key' => 'app_version',
        'value' => '1.0.0',
        'type' => 'string',
        'description' => 'Current version of the application',
        'group' => 'general',
        'is_public' => true,
      ],
      [
        'key' => 'default_currency',
        'value' => 'EUR',
        'type' => 'string',
        'description' => 'Default currency for the platform',
        'group' => 'general',
        'is_public' => true,
      ],
      [
        'key' => 'default_language',
        'value' => 'fr',
        'type' => 'string',
        'description' => 'Default language for the platform',
        'group' => 'general',
        'is_public' => true,
      ],

      // Platform Settings
      [
        'key' => 'max_agencies_per_user',
        'value' => 5,
        'type' => 'number',
        'description' => 'Maximum number of agencies a user can be associated with',
        'group' => 'platform',
        'is_public' => false,
      ],
      [
        'key' => 'enable_agency_registration',
        'value' => true,
        'type' => 'boolean',
        'description' => 'Allow new agencies to register on the platform',
        'group' => 'platform',
        'is_public' => false,
      ],
      [
        'key' => 'platform_commission_rate',
        'value' => 0.05,
        'type' => 'number',
        'description' => 'Platform commission rate (as decimal, e.g., 0.05 = 5%)',
        'group' => 'platform',
        'is_public' => false,
      ],

      // Maintenance Settings
      [
        'key' => 'maintenance_mode',
        'value' => false,
        'type' => 'boolean',
        'description' => 'Enable maintenance mode for the platform',
        'group' => 'maintenance',
        'is_public' => true,
      ],
      [
        'key' => 'maintenance_message',
        'value' => 'The platform is currently under maintenance. Please try again later.',
        'type' => 'string',
        'description' => 'Message displayed during maintenance mode',
        'group' => 'maintenance',
        'is_public' => true,
      ],
      [
        'key' => 'maintenance_end_time',
        'value' => null,
        'type' => 'datetime',
        'description' => 'Expected end time for maintenance',
        'group' => 'maintenance',
        'is_public' => true,
      ],

      // Notification Settings
      [
        'key' => 'email_notifications_enabled',
        'value' => true,
        'type' => 'boolean',
        'description' => 'Enable email notifications system-wide',
        'group' => 'notifications',
        'is_public' => false,
      ],
      [
        'key' => 'notification_sender_email',
        'value' => 'noreply@immo-platform.com',
        'type' => 'email',
        'description' => 'Default sender email for notifications',
        'group' => 'notifications',
        'is_public' => false,
      ],
      [
        'key' => 'notification_sender_name',
        'value' => 'Immo Platform',
        'type' => 'string',
        'description' => 'Default sender name for notifications',
        'group' => 'notifications',
        'is_public' => false,
      ],

      // Security Settings
      [
        'key' => 'password_min_length',
        'value' => 8,
        'type' => 'number',
        'description' => 'Minimum password length requirement',
        'group' => 'security',
        'is_public' => false,
      ],
      [
        'key' => 'session_timeout',
        'value' => 120,
        'type' => 'number',
        'description' => 'Session timeout in minutes',
        'group' => 'security',
        'is_public' => false,
      ],
      [
        'key' => 'enable_two_factor_auth',
        'value' => false,
        'type' => 'boolean',
        'description' => 'Enable two-factor authentication requirement',
        'group' => 'security',
        'is_public' => false,
      ],

      // Performance Settings
      [
        'key' => 'cache_enabled',
        'value' => true,
        'type' => 'boolean',
        'description' => 'Enable application caching',
        'group' => 'performance',
        'is_public' => false,
      ],
      [
        'key' => 'cache_ttl',
        'value' => 3600,
        'type' => 'number',
        'description' => 'Default cache TTL in seconds',
        'group' => 'performance',
        'is_public' => false,
      ],
      [
        'key' => 'max_file_upload_size',
        'value' => 10,
        'type' => 'number',
        'description' => 'Maximum file upload size in MB',
        'group' => 'performance',
        'is_public' => true,
      ],

      // Integration Settings
      [
        'key' => 'api_rate_limit',
        'value' => 1000,
        'type' => 'number',
        'description' => 'API rate limit per hour',
        'group' => 'integrations',
        'is_public' => false,
      ],
      [
        'key' => 'webhook_endpoints',
        'value' => [],
        'type' => 'array',
        'description' => 'List of webhook endpoints for external integrations',
        'group' => 'integrations',
        'is_public' => false,
      ],

      // Billing Settings
      [
        'key' => 'billing_cycle',
        'value' => 'monthly',
        'type' => 'string',
        'description' => 'Default billing cycle for agencies',
        'group' => 'billing',
        'is_public' => false,
      ],
      [
        'key' => 'trial_period_days',
        'value' => 30,
        'type' => 'number',
        'description' => 'Trial period duration in days for new agencies',
        'group' => 'billing',
        'is_public' => false,
      ],
    ];

    foreach ($settings as $setting) {
      SystemSetting::updateOrCreate(
        ['key' => $setting['key']],
        $setting
      );
    }
  }
}
