<?php

namespace App\Filament\Admin\Resources\OwnerResource\RelationManagers;

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

    protected static ?string $title = 'Reversements';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('owner_id')
            ->columns([
                TextColumn::make('remittance_date')
                    ->label('Date de remise')
                    ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->translatedFormat('d F Y')) // Traduire le mois
                    ->alignCenter(), // Centrer la colonne

                TextColumn::make('amount')
                    ->label('Montant versé')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', ' ') . ' FCFA')
                    ->alignCenter(), // Centrer la colonne

                TextColumn::make('amount_to_transfer')
                    ->label('Montant dû')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', ' ') . ' FCFA')
                    ->alignCenter(), // Centrer la colonne

                TextColumn::make('remaining')
                    ->label('Montant restant')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', ' ') . ' FCFA')
                    ->alignCenter(), // Centrer la colonne

                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Paid' => 'success',
                        'Partial' => 'warning',
                        'Pending' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'Paid' => 'heroicon-o-check-circle',
                        'Partial' => 'heroicon-o-clock',
                        'Pending' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->sortable()
                    ->alignCenter(), // Centrer la colonne
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

