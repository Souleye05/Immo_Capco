<?php

namespace App\Filament\Resources\OwnerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RemittanceRelationManager extends RelationManager
{
    protected static string $relationship = 'remittance';

    protected static ?string $recordTitleAttribute = 'owner_id';

    protected static ?string $pluralLabel = 'Remises';



    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('owner_id')
            ->columns([
                TextColumn::make('remittance_date')
                   ->label('Date de remise')
                   ->date('d/m/Y'),
                TextColumn::make('amount')
                   ->label('Montant')
                   ->money('XOF'),
                TextColumn::make('mode_remit')
                   ->label('Mode de remise'),   
            ])
            ->filters([
                
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

