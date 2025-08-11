<?php

namespace App\Filament\Owner\Resources;

use App\Enums\MonthEnum;
use App\Enums\PaymentType;
use App\Filament\Owner\Resources\RevenueResource\Pages;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class RevenueResource extends Resource
{
  protected static ?string $model = Payment::class;

  protected static ?string $navigationIcon = 'heroicon-o-banknotes';
  protected static ?string $navigationGroup = 'Mes Biens';
  protected static ?string $navigationLabel = 'Mes Revenus';
  protected static ?string $modelLabel = 'Paiement';
  protected static ?string $pluralModelLabel = 'Revenus';

  // Use tenant scoping to automatically filter by agency
  protected static ?string $tenantOwnershipRelationshipName = 'agency';

  public static function getEloquentQuery(): Builder
  {
    // Filter payments to show only those for properties owned by the authenticated user
    $user = auth()->user();

    // Validation: Ensure user is authenticated
    if (!$user) {
      throw new \Exception('User must be authenticated to access owner resources');
    }

    return parent::getEloquentQuery()
      ->where(function (Builder $query) use ($user) {
        // Primary: Filter by direct user_id relation through contract property owner
        $query->whereHas('contract.property', function (Builder $subQuery) use ($user) {
          $subQuery->whereHas('owner', function (Builder $ownerQuery) use ($user) {
            $ownerQuery->where('user_id', $user->id);
          });
        })
          // Fallback: Filter by email/name matching through contract property owner
          ->orWhereHas('contract.property', function (Builder $subQuery) use ($user) {
            $subQuery->whereHas('owner', function (Builder $ownerQuery) use ($user) {
              $ownerQuery->where('email', $user->email)
                ->orWhere('name', 'like', '%' . trim($user->name) . '%');
            });
          });
      })
      ->with(['contract.property', 'contract.tenant', 'contract.flat']);
  }

  public static function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\Section::make('Informations du paiement')
          ->schema([
            Forms\Components\TextInput::make('numero')
              ->label('N° Facture')
              ->disabled()
              ->dehydrated(false),

            Forms\Components\Select::make('type')
              ->label('Type de paiement')
              ->options(PaymentType::getOptions())
              ->disabled()
              ->dehydrated(false),

            Forms\Components\TextInput::make('current_month')
              ->label('Mois concerné')
              ->disabled()
              ->dehydrated(false),
          ])
          ->columns(3),

        Forms\Components\Section::make('Informations du bien')
          ->schema([
            Forms\Components\TextInput::make('contract.property.name')
              ->label('Propriété')
              ->disabled()
              ->dehydrated(false),

            Forms\Components\TextInput::make('contract.flat.type')
              ->label('Appartement')
              ->disabled()
              ->dehydrated(false)
              ->formatStateUsing(fn($record) => $record?->contract?->flat?->type?->label()),

            Forms\Components\TextInput::make('contract.tenant.name')
              ->label('Locataire')
              ->disabled()
              ->dehydrated(false),
          ])
          ->columns(3),

        Forms\Components\Section::make('Informations financières')
          ->schema([
            Forms\Components\TextInput::make('amount')
              ->label('Montant dû')
              ->disabled()
              ->dehydrated(false)
              ->formatStateUsing(fn($state) => number_format($state, 0, ',', ' ') . ' F CFA'),

            Forms\Components\TextInput::make('amount_paid')
              ->label('Montant payé')
              ->disabled()
              ->dehydrated(false)
              ->formatStateUsing(fn($state) => number_format($state, 0, ',', ' ') . ' F CFA'),

            Forms\Components\TextInput::make('amount_remaining')
              ->label('Montant restant')
              ->disabled()
              ->dehydrated(false)
              ->formatStateUsing(fn($state) => number_format($state, 0, ',', ' ') . ' F CFA'),

            Forms\Components\DatePicker::make('date_payment')
              ->label('Date de paiement')
              ->disabled()
              ->dehydrated(false),

            Forms\Components\Toggle::make('status')
              ->label('Payé')
              ->disabled()
              ->dehydrated(false),
          ])
          ->columns(3),
      ]);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('numero')
          ->label('N° Facture')
          ->searchable()
          ->sortable()
          ->copyable(),

        Tables\Columns\TextColumn::make('contract.property.name')
          ->label('Propriété')
          ->searchable()
          ->sortable(),

        Tables\Columns\TextColumn::make('contract.flat.type')
          ->label('Appartement')
          ->formatStateUsing(fn($record) => $record->contract?->flat?->type?->label())
          ->badge()
          ->color('gray'),

        Tables\Columns\TextColumn::make('contract.tenant.name')
          ->label('Locataire')
          ->searchable()
          ->sortable(),

        Tables\Columns\TextColumn::make('type')
          ->label('Type')
          ->badge()
          ->color(fn(PaymentType $state): string => match ($state) {
            PaymentType::LOYER => 'success',
            PaymentType::CAUTION => 'warning',
            PaymentType::COMMISSION => 'info',
          })
          ->formatStateUsing(fn(PaymentType $state) => $state->getLabel()),

        Tables\Columns\TextColumn::make('current_month')
          ->label('Mois')
          ->sortable()
          ->toggleable(),

        Tables\Columns\TextColumn::make('amount')
          ->label('Montant dû')
          ->formatStateUsing(fn($state) => number_format($state, 0, ',', ' ') . ' F CFA')
          ->sortable()
          ->alignEnd(),

        Tables\Columns\TextColumn::make('amount_paid')
          ->label('Montant payé')
          ->formatStateUsing(fn($state) => number_format($state, 0, ',', ' ') . ' F CFA')
          ->sortable()
          ->alignEnd()
          ->color('success'),

        Tables\Columns\TextColumn::make('amount_remaining')
          ->label('Restant')
          ->formatStateUsing(fn($state) => number_format($state, 0, ',', ' ') . ' F CFA')
          ->sortable()
          ->alignEnd()
          ->color(fn($state) => $state > 0 ? 'danger' : 'success'),

        Tables\Columns\IconColumn::make('status')
          ->label('Statut')
          ->boolean()
          ->trueIcon('heroicon-o-check-circle')
          ->falseIcon('heroicon-o-x-circle')
          ->trueColor('success')
          ->falseColor('danger'),

        Tables\Columns\TextColumn::make('date_payment')
          ->label('Date de paiement')
          ->date('d/m/Y')
          ->sortable()
          ->toggleable(),

        Tables\Columns\TextColumn::make('created_at')
          ->label('Créé le')
          ->dateTime('d/m/Y H:i')
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([
        Tables\Filters\SelectFilter::make('type')
          ->label('Type de paiement')
          ->options(PaymentType::getOptions())
          ->multiple(),

        Tables\Filters\SelectFilter::make('property_id')
          ->label('Propriété')
          ->relationship('contract.property', 'name')
          ->searchable()
          ->preload(),

        Tables\Filters\TernaryFilter::make('status')
          ->label('Statut de paiement')
          ->placeholder('Tous')
          ->trueLabel('Payés')
          ->falseLabel('Non payés'),

        Tables\Filters\Filter::make('current_month')
          ->form([
            Forms\Components\Select::make('month')
              ->label('Mois')
              ->options(MonthEnum::getOptions())
              ->native(false),

            Forms\Components\Select::make('year')
              ->label('Année')
              ->options(self::getYearOptions())
              ->native(false),
          ])
          ->query(function (Builder $query, array $data): Builder {
            return $query
              ->when(
                $data['month'],
                fn(Builder $query, $month): Builder => $query->where('current_month', 'like', '%' . MonthEnum::from($month)->getLabel() . '%')
              )
              ->when(
                $data['year'],
                fn(Builder $query, $year): Builder => $query->where('current_month', 'like', '%' . $year . '%')
              );
          })
          ->indicateUsing(function (array $data): array {
            $indicators = [];

            if ($data['month'] ?? null) {
              $month = MonthEnum::from($data['month']);
              $indicators['month'] = 'Mois: ' . $month->getLabel();
            }

            if ($data['year'] ?? null) {
              $indicators['year'] = 'Année: ' . $data['year'];
            }

            return $indicators;
          }),

        Tables\Filters\Filter::make('date_range')
          ->form([
            Forms\Components\DatePicker::make('created_from')
              ->label('Créé à partir du'),
            Forms\Components\DatePicker::make('created_until')
              ->label('Créé jusqu\'au'),
          ])
          ->query(function (Builder $query, array $data): Builder {
            return $query
              ->when(
                $data['created_from'],
                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
              )
              ->when(
                $data['created_until'],
                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
              );
          })
          ->indicateUsing(function (array $data): array {
            $indicators = [];

            if ($data['created_from'] ?? null) {
              $indicators['created_from'] = 'Créé à partir du ' . Carbon::parse($data['created_from'])->format('d/m/Y');
            }

            if ($data['created_until'] ?? null) {
              $indicators['created_until'] = 'Créé jusqu\'au ' . Carbon::parse($data['created_until'])->format('d/m/Y');
            }

            return $indicators;
          }),
      ])
      ->actions([
        Tables\Actions\ViewAction::make(),

        Tables\Actions\Action::make('download_receipt')
          ->label('Reçu')
          ->icon('heroicon-o-document-arrow-down')
          ->color('info')
          ->visible(fn($record) => $record->status)
          ->url(fn($record): string => route('payments.receipt', $record))
          ->openUrlInNewTab(),
      ])
      ->defaultSort('created_at', 'desc')
      ->striped();
  }

  private static function getYearOptions(): array
  {
    $years = [];
    $currentYear = Carbon::now()->year;

    for ($i = $currentYear - 2; $i <= $currentYear + 1; $i++) {
      $years[$i] = $i;
    }

    return $years;
  }

  public static function getPages(): array
  {
    return [
      'index' => Pages\ListRevenues::route('/'),
      'view' => Pages\ViewRevenue::route('/{record}'),
    ];
  }

  public static function canCreate(): bool
  {
    return false; // Owners cannot create payments
  }

  public static function canEdit($record): bool
  {
    return false; // Owners cannot edit payments
  }

  public static function canDelete($record): bool
  {
    return false; // Owners cannot delete payments
  }
}
