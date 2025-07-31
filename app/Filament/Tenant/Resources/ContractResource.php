<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\ContractResource\Pages;
use App\Models\Contract;
use App\Models\Tenant;
use App\Enums\ContractStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Support\Colors\Color;
use Carbon\Carbon;

class ContractResource extends Resource
{
    protected static ?string $model = Contract::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Mes Contrats';

    protected static ?string $modelLabel = 'Contrat';

    protected static ?string $pluralModelLabel = 'Contrats';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        // Filter contracts for the authenticated tenant user
        // Assuming tenant authentication is done by matching user with tenant records
        $user = auth()->user();

        return parent::getEloquentQuery()
            ->whereHas('tenant', function (Builder $query) use ($user) {
                // Match by name or email - adjust this logic based on your authentication system
                $query->where('name', 'like', '%' . $user->name . '%')
                    ->orWhere('phone', 'like', '%' . $user->email . '%');
            })
            ->with(['tenant', 'property', 'flat', 'agency', 'payments']);
    }

    public static function form(Form $form): Form
    {
        // Tenant panel should be read-only for contracts
        return $form
            ->schema([
                Forms\Components\Placeholder::make('readonly_notice')
                    ->content('Les informations de contrat ne peuvent pas être modifiées. Contactez votre agence pour toute question.')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('contract_number')
                    ->label('N° Contrat')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('property.name')
                    ->label('Propriété')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('flat.type')
                    ->label('Logement')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('agency.name')
                    ->label('Agence')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->colors([
                        'success' => ContractStatus::ACTIVE,
                        'warning' => ContractStatus::DRAFT,
                        'danger' => ContractStatus::TERMINATED,
                        'secondary' => ContractStatus::EXPIRED,
                    ])
                    ->formatStateUsing(fn(ContractStatus $state): string => match ($state) {
                        ContractStatus::ACTIVE => 'Actif',
                        ContractStatus::DRAFT => 'En attente',
                        ContractStatus::TERMINATED => 'Résilié',
                        ContractStatus::EXPIRED => 'Expiré',
                    }),

                TextColumn::make('monthly_rent')
                    ->label('Loyer mensuel')
                    ->money('XOF')
                    ->sortable(),

                TextColumn::make('start_date')
                    ->label('Date début')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label('Date fin')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(
                        fn(Contract $record): string =>
                        $record->end_date && $record->end_date->isPast() ? 'danger' : ($record->end_date && $record->end_date->diffInDays() <= 90 ? 'warning' : 'success')
                    ),

                TextColumn::make('payments_count')
                    ->label('Paiements')
                    ->counts('payments')
                    ->sortable(),

                TextColumn::make('days_until_expiration')
                    ->label('Jours restants')
                    ->getStateUsing(function (Contract $record): string {
                        return $record->end_date ?
                            (Carbon::now()->diffInDays($record->end_date, false) . ' jours') :
                            'N/A';
                    })
                    ->color(function (Contract $record): string {
                        if (!$record->end_date) return 'secondary';
                        $days = Carbon::now()->diffInDays($record->end_date, false);
                        return $days < 0 ? 'danger' : ($days <= 90 ? 'warning' : 'success');
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        ContractStatus::ACTIVE->value => 'Actif',
                        ContractStatus::DRAFT->value => 'brouillon',
                        ContractStatus::TERMINATED->value => 'Résilié',
                        ContractStatus::EXPIRED->value => 'Expiré',
                    ]),

                SelectFilter::make('agency_id')
                    ->label('Agence')
                    ->relationship('agency', 'name'),

                Filter::make('expiring_soon')
                    ->label('Expire bientôt')
                    ->query(
                        fn(Builder $query): Builder =>
                        $query->where('end_date', '<=', Carbon::now()->addDays(90))
                            ->where('end_date', '>', Carbon::now())
                    ),

                Filter::make('active_contracts')
                    ->label('Contrats actifs')
                    ->query(
                        fn(Builder $query): Builder =>
                        $query->where('status', ContractStatus::ACTIVE)
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Voir détails'),
            ])
            ->bulkActions([
                // No bulk actions for tenant panel
            ])
            ->defaultSort('start_date', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Aucun contrat trouvé')
            ->emptyStateDescription('Vous n\'avez aucun contrat enregistré.')
            ->emptyStateIcon('heroicon-o-document-text');
    }

    public static function getRelations(): array
    {
        return [
            // Add relation managers if needed
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContracts::route('/'),
            'view' => Pages\ViewContract::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Tenants cannot create contracts
    }

    public static function canEdit(Model $record): bool
    {
        return false; // Tenants cannot edit contracts
    }

    public static function canDelete(Model $record): bool
    {
        return false; // Tenants cannot delete contracts
    }

    public static function getNavigationBadge(): ?string
    {
        // Show number of contracts expiring soon
        $expiringSoonCount = static::getEloquentQuery()
            ->where('status', ContractStatus::ACTIVE)
            ->where('end_date', '<=', Carbon::now()->addDays(90))
            ->where('end_date', '>', Carbon::now())
            ->count();

        return $expiringSoonCount > 0 ? (string) $expiringSoonCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() ? 'warning' : null;
    }
}
