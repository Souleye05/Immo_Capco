<?php

namespace App\Filament\Resources;

use App\Enums\PaymentType;
use App\Filament\Resources\VersementResource\Pages;
use App\Models\Versement;
use App\Services\VersementService;
use App\Traits\PaymentMethodHelper;
use App\Traits\VersementLogicHelper;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;

class VersementResource extends Resource
{
    use PaymentMethodHelper, VersementLogicHelper;

    protected static ?string $model = Versement::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Versements';
    protected static ?string $navigationGroup = 'Paiement';

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
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', ' ') . ' FCFA'),
                    
                Tables\Columns\TextColumn::make('versement_date')
                    ->label('Date de versement')
                    ->alignCenter()
                    ->date(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Mode de versement')
                    ->alignCenter()
                    ->badge()
                    ->color(fn (Versement $record) => static::getPaymentMethodColor($record->payment_method))
                    ->icon(fn (Versement $record) => static::getPaymentMethodIcon($record->payment_method))
                    ->formatStateUsing(fn (string $state) => static::getPaymentMethodLabel($state)),
            ])
            ->actions([
                Action::make('download_recu')
                    ->label('Reçu')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('info')
                    ->visible(fn (Versement $record) => !static::isSingleFullPayment($record))
                    ->url(fn (Versement $record) => route('documents.download-recu', $record))
                    ->openUrlInNewTab(),

                Action::make('download_quittance')
                    ->label('Quittance')
                    ->icon('heroicon-o-document-check')
                    ->color('success')
                    ->visible(fn (Versement $record) => static::isSingleFullPayment($record))
                    ->url(fn (Versement $record) => route('documents.download-quittance', $record->payment))
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

    
}