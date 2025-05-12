<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UnsoldResource\Pages;
use App\Filament\Resources\UnsoldResource\RelationManagers;
use App\Models\Flat;
use App\Models\Unsold;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Tenant;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;

class UnsoldResource extends Resource
{
    protected static ?string $model = Unsold::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Paiement';

    public static ?string $label = 'impayées';

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            TextInput::make('reference')
                ->default('UN-' . random_int(100000, 999999))
                ->disabled()
                ->dehydrated(true)
                ->required(),

            Select::make('tenant_id')
                ->label('Locataire')
                ->options(function () {
                    // Récupérer uniquement les locataires ayant des factures avec un statut 0
                    return Tenant::whereHas('payment', function ($query) {
                        $query->where('status', 0); // Statut 0 = facture non payée
                    })->pluck('name', 'id');
                })
                ->searchable()
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    if ($state) {
                        // Récupérer les informations de paiement pour le locataire sélectionné
                        $tenant = Tenant::find($state);
                        if ($tenant) {
                            $payments = Payment::where('tenant_id', $state)->get();

                            $totalDue = $payments->sum('amount'); // Montant total dû
                            $totalPaid = $payments->sum('amount_paid'); // Montant total versé
                            $remaining = max(0, $totalDue - $totalPaid); // Montant restant

                            $set('amount_to_pay', $totalDue);
                            $set('amount_paid', $totalPaid);
                            $set('amount_remaining', $remaining);
                        }
                    }
                }),

            TextInput::make('amount_to_pay')
                ->label('Montant à verser')
                ->disabled(),

            TextInput::make('amount_paid')
                ->label('Montant versé')
                ->disabled(),

            TextInput::make('amount_remaining')
                ->label('Montant restant')
                ->disabled(),

            Textarea::make('motif')
                ->maxLength(65535),
            Toggle::make('status')
                ->label('État')
                ->disabled(),
            DatePicker::make('date')
                ->label('Date de l\'impayé')
                ->default(now())
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Référence')
                    ->alignCenter()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Locataire')
                    ->alignCenter()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount_to_pay')
                    ->label('Montant à verser')
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', ' ') . ' F CFA')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount_paid')
                    ->label('Montant versé')
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', ' ') . ' F CFA')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount_remaining')
                    ->label('Montant restant')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', ' ') . ' F CFA')
                    ->color('danger')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('motif')
                    ->label('Motif')
                    ->limit(50) // Limite l'affichage à 50 caractères
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('status')
                    ->label('État')
                    ->boolean()
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Mis à jour le')
                    ->dateTime('d/m/Y H:i')
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
                // Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
            ->action(function ($record) {
                    $record->forceDelete(); // Suppression définitive
                Notification::make()
                        ->success()
                        ->title('Supprimé')
                        ->body('L\'impayé a été supprimé avec succès.')
                        ->send();
    })
    ->requiresConfirmation(),
    ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUnsolds::route('/'),
            'create' => Pages\CreateUnsold::route('/create'),
            'edit' => Pages\EditUnsold::route('/{record}/edit'),
        ];
    }
}
