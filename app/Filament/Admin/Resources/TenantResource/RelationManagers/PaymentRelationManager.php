<?php

namespace App\Filament\Admin\Resources\TenantResource\RelationManagers;

use Coolsam\FilamentFlatpickr\Forms\Components\Flatpickr;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentRelationManager extends RelationManager
{
    protected static string $relationship = 'payment';

    protected static ?string $recordTitleAttribute = 'amount';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('amount')
                    ->label('Montant')
                    ->required()
                    ->maxLength(255),

                Flatpickr::make('current_month')->monthSelect(),

                DatePicker::make('payment_date')
                    ->label('Date de versement')
                    ->required(),

                Select::make('payment_method')
                    ->label('Moyen de versement')
                    ->searchable()
                    ->options([
                        'OM' => 'OM',
                        'Wave' => 'Wave',
                        'Free Money' => 'Free Money',
                        'Chèque' => 'Chèque',
                        'Virement' => 'Vrirement',
                        'Espèces' => 'Espèces',
                    ]),
                    
                  Toggle::make('status')
                    ->label('Etat du paiement')
                    ->inline(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('amount')
            ->columns([
                Tables\Columns\TextColumn::make('flat.reference')
                    ->label('Appartement'),

                Tables\Columns\TextColumn::make('numero')
                    ->label('Numéro de facture'),    

                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant')
                    ->money('XOF'),

                Tables\Columns\TextColumn::make('current_month')
                    ->label('Mois de')
                    ->date(),    

                Tables\Columns\TextColumn::make('date_payment')
                    ->label('Date de versement')
                    ->date('d/m/Y'),

                    Tables\Columns\IconColumn::make('status')
                    ->label('Statut')
                    ->sortable()
                    ->boolean()  // Ceci convertit automatiquement 1/0 en icônes
                    ->trueIcon('heroicon-o-check-circle')  // Icône pour paiement complet
                    ->falseIcon('heroicon-o-x-circle')     // Icône pour paiement incomplet
                    ->trueColor('success')                 // Couleur verte pour paiement complet
                    ->falseColor('danger')                 // Couleur rouge pour paiement incomplet
                    ->toggleable(),    
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
