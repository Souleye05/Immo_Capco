<?php

namespace App\Filament\Owner\Resources\TenantResource\Pages;

use App\Enums\ContractStatus;
use App\Filament\Owner\Resources\TenantResource;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;

class ViewTenant extends ViewRecord
{
  protected static string $resource = TenantResource::class;

  protected function getHeaderActions(): array
  {
    return [
      // No edit or delete actions since owners cannot modify tenants
    ];
  }

  /**
   * Configure the form schema for displaying tenant details in read-only mode
   */
  public function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\Section::make('Informations de contact')
          ->description('Informations personnelles du locataire')
          ->icon('heroicon-o-user')
          ->schema([
            Forms\Components\Grid::make(2)
              ->schema([
                Forms\Components\TextInput::make('name')
                  ->label('Nom complet')
                  ->disabled()
                  ->dehydrated(false)
                  ->prefixIcon('heroicon-o-user'),

                Forms\Components\TextInput::make('email')
                  ->label('Adresse email')
                  ->disabled()
                  ->dehydrated(false)
                  ->prefixIcon('heroicon-o-envelope')
                  ->formatStateUsing(function ($state) {
                    // Mask email for privacy (show first 3 chars and domain)
                    if (!$state) return '-';
                    $parts = explode('@', $state);
                    if (count($parts) !== 2) return $state;
                    $username = $parts[0];
                    $domain = $parts[1];
                    $maskedUsername = strlen($username) > 3
                      ? substr($username, 0, 3) . str_repeat('*', strlen($username) - 3)
                      : $username;
                    return $maskedUsername . '@' . $domain;
                  }),

                Forms\Components\TextInput::make('phone')
                  ->label('Numéro de téléphone')
                  ->disabled()
                  ->dehydrated(false)
                  ->prefixIcon('heroicon-o-phone')
                  ->formatStateUsing(function ($state) {
                    // Mask phone number for privacy (show last 4 digits)
                    if (!$state) return '-';
                    $cleanPhone = preg_replace('/[^0-9]/', '', $state);
                    if (strlen($cleanPhone) <= 4) return $state;
                    return str_repeat('*', strlen($cleanPhone) - 4) . substr($cleanPhone, -4);
                  }),

                Forms\Components\Textarea::make('address')
                  ->label('Adresse')
                  ->disabled()
                  ->dehydrated(false)
                  ->rows(2)
                  ->columnSpanFull()
                  ->formatStateUsing(function ($state) {
                    // Show address but mask specific details if needed
                    return $state ?: 'Non renseignée';
                  }),
              ]),
          ])
          ->collapsible(),

        Forms\Components\Section::make('Contrat actuel')
          ->description('Informations sur le contrat en cours')
          ->icon('heroicon-o-document-text')
          ->schema([
            Forms\Components\Grid::make(3)
              ->schema([
                Forms\Components\TextInput::make('active_contract.contract_number')
                  ->label('Numéro de contrat')
                  ->disabled()
                  ->dehydrated(false)
                  ->formatStateUsing(function ($record) {
                    $activeContract = $record->contracts->where('status', ContractStatus::ACTIVE)->first();
                    return $activeContract?->contract_number ?? 'Aucun contrat actif';
                  }),

                Forms\Components\TextInput::make('active_contract.property.name')
                  ->label('Propriété')
                  ->disabled()
                  ->dehydrated(false)
                  ->formatStateUsing(function ($record) {
                    $activeContract = $record->contracts->where('status', ContractStatus::ACTIVE)->first();
                    return $activeContract?->property?->name ?? 'Non définie';
                  }),

                Forms\Components\TextInput::make('active_contract.status')
                  ->label('Statut')
                  ->disabled()
                  ->dehydrated(false)
                  ->formatStateUsing(function ($record) {
                    $activeContract = $record->contracts->where('status', ContractStatus::ACTIVE)->first();
                    return $activeContract?->status?->label() ?? 'Aucun contrat';
                  }),

                Forms\Components\TextInput::make('active_contract.start_date')
                  ->label('Date de début')
                  ->disabled()
                  ->dehydrated(false)
                  ->formatStateUsing(function ($record) {
                    $activeContract = $record->contracts->where('status', ContractStatus::ACTIVE)->first();
                    return $activeContract?->start_date?->format('d/m/Y') ?? '-';
                  }),

                Forms\Components\TextInput::make('active_contract.end_date')
                  ->label('Date de fin')
                  ->disabled()
                  ->dehydrated(false)
                  ->formatStateUsing(function ($record) {
                    $activeContract = $record->contracts->where('status', ContractStatus::ACTIVE)->first();
                    return $activeContract?->end_date?->format('d/m/Y') ?? '-';
                  }),

                Forms\Components\TextInput::make('active_contract.monthly_rent')
                  ->label('Loyer mensuel')
                  ->disabled()
                  ->dehydrated(false)
                  ->formatStateUsing(function ($record) {
                    $activeContract = $record->contracts->where('status', ContractStatus::ACTIVE)->first();
                    if (!$activeContract || !$activeContract->monthly_rent) return '-';
                    return number_format($activeContract->monthly_rent, 0, ',', ' ') . ' F CFA';
                  }),

                Forms\Components\TextInput::make('contract_duration')
                  ->label('Durée du contrat')
                  ->disabled()
                  ->dehydrated(false)
                  ->formatStateUsing(function ($record) {
                    $activeContract = $record->contracts->where('status', ContractStatus::ACTIVE)->first();
                    if (!$activeContract) return '-';

                    $startDate = $activeContract->start_date;
                    $endDate = $activeContract->end_date;

                    if (!$startDate || !$endDate) return '-';

                    $months = $startDate->diffInMonths($endDate);
                    return $months . ' mois';
                  }),

                Forms\Components\TextInput::make('days_until_expiration')
                  ->label('Jours avant expiration')
                  ->disabled()
                  ->dehydrated(false)
                  ->formatStateUsing(function ($record) {
                    $activeContract = $record->contracts->where('status', ContractStatus::ACTIVE)->first();
                    if (!$activeContract || !$activeContract->end_date) return '-';

                    $daysUntil = now()->diffInDays($activeContract->end_date, false);

                    if ($daysUntil < 0) {
                      return 'Expiré depuis ' . abs($daysUntil) . ' jours';
                    } elseif ($daysUntil === 0) {
                      return 'Expire aujourd\'hui';
                    } else {
                      return $daysUntil . ' jours';
                    }
                  }),

                Forms\Components\Textarea::make('active_contract.notes')
                  ->label('Notes du contrat')
                  ->disabled()
                  ->dehydrated(false)
                  ->rows(2)
                  ->columnSpanFull()
                  ->formatStateUsing(function ($record) {
                    $activeContract = $record->contracts->where('status', ContractStatus::ACTIVE)->first();
                    return $activeContract?->notes ?? 'Aucune note';
                  }),
              ]),
          ])
          ->collapsible(),

        Forms\Components\Section::make('Statut des paiements')
          ->description('Informations sur les paiements du locataire')
          ->icon('heroicon-o-banknotes')
          ->schema([
            Forms\Components\Grid::make(3)
              ->schema([
                Forms\Components\TextInput::make('payment_status')
                  ->label('Statut général')
                  ->disabled()
                  ->dehydrated(false)
                  ->formatStateUsing(function ($record) {
                    try {
                      // Check for overdue payments
                      $overdueCount = $record->allPayments()
                        ->where('status', false)
                        ->where('date_payment', '<', now())
                        ->count();

                      if ($overdueCount > 0) {
                        return "En retard ({$overdueCount} paiements)";
                      }

                      // Check for partial payments
                      $payments = $record->allPayments()->with('versement')->get();
                      $partialCount = $payments->filter(function ($payment) {
                        $amountPaid = $payment->versement->sum('amount');
                        return $amountPaid > 0 && $amountPaid < $payment->amount;
                      })->count();

                      if ($partialCount > 0) {
                        return "Partiel ({$partialCount} paiements)";
                      }

                      return 'À jour';
                    } catch (\Exception $e) {
                      return 'Erreur de calcul';
                    }
                  }),

                Forms\Components\TextInput::make('total_payments')
                  ->label('Total des paiements')
                  ->disabled()
                  ->dehydrated(false)
                  ->formatStateUsing(function ($record) {
                    try {
                      $totalAmount = $record->allPayments()->sum('amount');
                      return number_format($totalAmount, 0, ',', ' ') . ' F CFA';
                    } catch (\Exception $e) {
                      return 'Erreur de calcul';
                    }
                  }),

                Forms\Components\TextInput::make('total_paid')
                  ->label('Total versé')
                  ->disabled()
                  ->dehydrated(false)
                  ->formatStateUsing(function ($record) {
                    try {
                      $payments = $record->allPayments()->with('versement')->get();
                      $totalPaid = $payments->sum(function ($payment) {
                        return $payment->versement->sum('amount');
                      });
                      return number_format($totalPaid, 0, ',', ' ') . ' F CFA';
                    } catch (\Exception $e) {
                      return 'Erreur de calcul';
                    }
                  }),

                Forms\Components\TextInput::make('outstanding_amount')
                  ->label('Montant en attente')
                  ->disabled()
                  ->dehydrated(false)
                  ->formatStateUsing(function ($record) {
                    try {
                      $payments = $record->allPayments()->with('versement')->get();
                      $totalAmount = $payments->sum('amount');
                      $totalPaid = $payments->sum(function ($payment) {
                        return $payment->versement->sum('amount');
                      });
                      $outstanding = $totalAmount - $totalPaid;
                      return number_format(max(0, $outstanding), 0, ',', ' ') . ' F CFA';
                    } catch (\Exception $e) {
                      return 'Erreur de calcul';
                    }
                  }),

                Forms\Components\TextInput::make('last_payment_date')
                  ->label('Dernier paiement')
                  ->disabled()
                  ->dehydrated(false)
                  ->formatStateUsing(function ($record) {
                    try {
                      $lastPayment = $record->allPayments()
                        ->where('status', true)
                        ->orderBy('date_payment', 'desc')
                        ->first();

                      return $lastPayment?->date_payment
                        ? \Carbon\Carbon::parse($lastPayment->date_payment)->format('d/m/Y')
                        : 'Aucun paiement';
                    } catch (\Exception $e) {
                      return 'Erreur';
                    }
                  }),

                Forms\Components\TextInput::make('next_payment_due')
                  ->label('Prochain paiement dû')
                  ->disabled()
                  ->dehydrated(false)
                  ->formatStateUsing(function ($record) {
                    try {
                      $nextPayment = $record->allPayments()
                        ->where('status', false)
                        ->where('date_payment', '>=', now())
                        ->orderBy('date_payment', 'asc')
                        ->first();

                      return $nextPayment?->date_payment
                        ? \Carbon\Carbon::parse($nextPayment->date_payment)->format('d/m/Y')
                        : 'Aucun paiement prévu';
                    } catch (\Exception $e) {
                      return 'Erreur';
                    }
                  }),
              ]),
          ])
          ->collapsible(),

        Forms\Components\Section::make('Informations système')
          ->description('Données techniques et audit')
          ->icon('heroicon-o-cog-6-tooth')
          ->schema([
            Forms\Components\Grid::make(2)
              ->schema([
                Forms\Components\TextInput::make('created_at')
                  ->label('Date d\'ajout')
                  ->disabled()
                  ->dehydrated(false)
                  ->formatStateUsing(function ($state) {
                    return $state ? \Carbon\Carbon::parse($state)->format('d/m/Y à H:i') : '-';
                  }),

                Forms\Components\TextInput::make('updated_at')
                  ->label('Dernière modification')
                  ->disabled()
                  ->dehydrated(false)
                  ->formatStateUsing(function ($state) {
                    return $state ? \Carbon\Carbon::parse($state)->format('d/m/Y à H:i') : '-';
                  }),

                Forms\Components\TextInput::make('total_contracts')
                  ->label('Nombre de contrats')
                  ->disabled()
                  ->dehydrated(false)
                  ->formatStateUsing(function ($record) {
                    return $record->contracts->count() . ' contrat(s)';
                  }),

                Forms\Components\TextInput::make('agency.name')
                  ->label('Agence')
                  ->disabled()
                  ->dehydrated(false)
                  ->formatStateUsing(function ($record) {
                    return $record->agency?->name ?? 'Non définie';
                  }),
              ]),
          ])
          ->collapsible()
          ->collapsed(),
      ]);
  }

  /**
   * Authorize access to the tenant record
   */
  protected function authorizeAccess(): void
  {
    parent::authorizeAccess();

    $user = auth()->user();
    $tenant = $this->getRecord();

    // Enhanced authorization check
    if (!$user) {
      throw new AuthorizationException('User must be authenticated to view tenant details');
    }

    // Check if user can access owner panel
    if (!$user->canAccessOwnerPanel()) {
      throw new AuthorizationException('User is not authorized to access owner panel');
    }

    // Validate tenant access using the resource method
    if (!TenantResource::validateTenantAccess($tenant, $user)) {
      // Log unauthorized access attempt
      \Log::warning('Unauthorized tenant view page access attempt', [
        'user_id' => $user->id,
        'user_email' => $user->email,
        'tenant_id' => $tenant->id,
        'tenant_name' => $tenant->name,
        'ip' => request()->ip(),
        'user_agent' => request()->userAgent(),
        'route' => request()->route()?->getName(),
      ]);

      throw new AuthorizationException('You are not authorized to view this tenant');
    }

    // Log successful access for audit trail
    TenantResource::logTenantAccess($tenant, 'view_page');
  }

  /**
   * Resolve the record with additional security checks and eager loading
   */
  public function resolveRecord($key): Model
  {
    // Load the tenant with all necessary relationships for the form
    $tenant = Tenant::with([
      'contracts' => function ($query) {
        $query->with(['property.owner', 'payments.versement']);
      },
      'agency',
      'user'
    ])->findOrFail($key);

    // Additional validation to ensure the tenant belongs to the owner
    $user = auth()->user();
    if ($user && !TenantResource::validateTenantAccess($tenant, $user)) {
      // Log the attempt and throw exception
      \Log::warning('Attempted access to unauthorized tenant record', [
        'user_id' => $user->id,
        'tenant_id' => $key,
        'ip' => request()->ip(),
      ]);

      throw new AuthorizationException('Tenant not found or access denied');
    }

    return $tenant;
  }

  /**
   * Handle mount with security validation
   */
  public function mount(int | string $record): void
  {
    try {
      parent::mount($record);

      // Additional security validation after mounting
      $this->authorizeAccess();
    } catch (AuthorizationException $e) {
      // Redirect to tenant list with error message
      $this->redirect(TenantResource::getUrl('index'), navigate: true);

      // Show error notification
      \Filament\Notifications\Notification::make()
        ->title('Accès refusé')
        ->body('Vous n\'êtes pas autorisé à consulter ce locataire.')
        ->danger()
        ->send();

      return;
    } catch (\Exception $e) {
      // Log unexpected errors
      \Log::error('Error mounting ViewTenant page', [
        'user_id' => auth()->id(),
        'record' => $record,
        'error' => $e->getMessage(),
      ]);

      $this->redirect(TenantResource::getUrl('index'), navigate: true);

      \Filament\Notifications\Notification::make()
        ->title('Erreur')
        ->body('Une erreur est survenue lors du chargement des détails du locataire.')
        ->danger()
        ->send();

      return;
    }
  }

  /**
   * Get the page title with security context
   */
  public function getTitle(): string
  {
    $tenant = $this->getRecord();

    // Mask sensitive information in title if needed
    return "Détails du locataire : " . $tenant->name;
  }

  /**
   * Get breadcrumbs with proper navigation
   */
  public function getBreadcrumbs(): array
  {
    return [
      TenantResource::getUrl('index') => 'Mes Locataires',
      '#' => $this->getTitle(),
    ];
  }
}
