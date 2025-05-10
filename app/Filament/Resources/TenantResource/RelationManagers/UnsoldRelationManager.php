<?php

namespace App\Filament\Resources\TenantResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UnsoldRelationManager extends RelationManager
{
    protected static string $relationship = 'unsold';

    protected static ?string $recordTitleAttribute = 'tenant_id';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('reference')
                    ->default('Un' . random_int(100000, 999999))
                    ->disabled()
                    ->required(),

                Forms\Components\TextInput::make('amount'),
                Forms\Components\TextInput::make('motif') 
                   ->maxLength(255),
                Forms\Components\Toggle::make('etat')
                  ->disabled(),
                Forms\Components\DatePicker::make('date'),          
            ]);
    }

    public function table(Table $table): Table
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

                // Tables\Columns\IconColumn::make('status')
                //     ->label('État')
                //     ->boolean()
                //     ->alignCenter()
                //     ->sortable(),

                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->alignCenter()
                    ->sortable(),
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
