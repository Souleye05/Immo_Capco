<?php

namespace App\Filament\Admin\Resources;

use App\Enums\PaymentType;
use App\Filament\Admin\Resources\VersementResource\Pages;
use App\Models\Versement;
use App\Services\VersementService;
use App\Traits\HasAgencyPermissions;
use App\Traits\PaymentMethodHelper;
use App\Traits\VersementLogicHelper;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;

class VersementResource extends Resource
{
    use HasAgencyPermissions, PaymentMethodHelper, VersementLogicHelper;

    protected static ?string $model = Versement::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Versements';
    protected static ?string $navigationGroup = 'Paiement';

    // Désactiver le tenant scoping automatique
    protected static ?string $tenantOwnershipRelationshipName = null;

    // Désactiver complètement le tenant scoping
    public static function isScopedToTenant(): bool
    {
        return false;
    }

    // Permissions spécifiques pour la gestion des versements
    protected static function getViewPermission(): string
    {
        return 'view_payments'; // Les versements sont liés aux paiements
    }

    protected static function getCreatePermission(): string
    {
        return 'create_payments';
    }

    protected static function getEditPermission(): string
    {
        return 'edit_payments';
    }

    protected static function getDeletePermission(): string
    {
        return 'delete_payments';
    }

    protected static function getVersementService(): VersementService
    {
        return app(VersementService::class);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment.type')
                    ->label('Type de facture')
                    ->sortable()
                    ->badge()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('payment.numero')
                    ->searchable()
                    ->alignCenter()
                    ->sortable()
                    ->label('Facture'),
                Tables\Columns\TextColumn::make('payment.tenant.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant')
                    ->alignCenter()
                    ->formatStateUsing(fn($state) => number_format($state, 0, ',', ' ') . ' FCFA'),

                Tables\Columns\TextColumn::make('versement_date')
                    ->label('Date de versement')
                    ->alignCenter()
                    ->date(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Mode de versement')
                    ->alignCenter()
                    ->badge()
                    ->color(fn(Versement $record) => static::getPaymentMethodColor($record->payment_method))
                    ->icon(fn(Versement $record) => static::getPaymentMethodIcon($record->payment_method))
                    ->formatStateUsing(fn(string $state) => static::getPaymentMethodLabel($state)),
            ])
            ->actions([
                Action::make('download_recu')
                    ->label('Reçu')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('info')
                    ->visible(fn(Versement $record) => !static::isSingleFullPayment($record))
                    ->url(fn(Versement $record) => route('documents.download-recu', $record))
                    ->openUrlInNewTab(),

                Action::make('download_quittance')
                    ->label('Quittance')
                    ->icon('heroicon-o-document-check')
                    ->color('success')
                    ->visible(fn(Versement $record) => static::isSingleFullPayment($record))
                    ->url(fn(Versement $record) => route('documents.download-quittance', $record->payment))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('versement_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVersements::route('/'),
        ];
    }

    // Scoping personnalisé pour filtrer par agence via payment
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        if ($tenant = \Filament\Facades\Filament::getTenant()) {
            // Debug: Log pour voir si cette méthode est appelée
            \Log::info('VersementResource getEloquentQuery called with tenant: ' . $tenant->name . ' (ID: ' . $tenant->id . ')');

            $query->whereHas('payment', function ($subQuery) use ($tenant) {
                $subQuery->where('agency_id', $tenant->id);
            });
        } else {
            \Log::info('VersementResource getEloquentQuery called but no tenant found');
        }

        return $query;
    }
}
