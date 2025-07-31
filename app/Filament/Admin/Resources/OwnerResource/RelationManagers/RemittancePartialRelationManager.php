<?php

namespace App\Filament\Admin\Resources\OwnerResource\RelationManagers;

use App\Models\RemittancePartial;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RemittancePartialRelationManager extends RelationManager
{
    protected static string $relationship = 'remittancePartials';

    protected static ?string $title = 'Versements partiels';


    protected function getTableQuery(): Builder
    {
        return RemittancePartial::query()
            ->whereHas('remittance.property', function (Builder $query) {
                $query->where('owner_id', $this->getOwnerRecord()->id);
            });
    }


    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->columns([
                Tables\Columns\TextColumn::make('remittance.property.name')
                    ->label('Propriété')
                    ->searchable()
                    ->alignCenter() // Centrer la colonne
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference')
                    ->label('Référence')
                    ->alignCenter() // Centrer la colonne
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant versé')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', ' ') . ' FCFA')
                    ->alignCenter() // Centrer la colonne
                    ->sortable(),
                Tables\Columns\TextColumn::make('mode_remit')
                ->label('Mode de versement')
                ->alignCenter() // Centrer la colonne
                ->badge()
                ->color( fn (string $state): string => match ($state) {
                    'OM' => 'danger',
                    'Wave' => 'info',
                    'Free Money' => 'yellow',
                    'Chèque' => 'warning',
                    'Virement' => 'info',
                    'Espèces' => 'danger',
                    default => 'secondary',
                })
                ->icon(fn (string $state): string => match ($state) {
                    'OM' => 'heroicon-o-device-phone-mobile',
                    'Wave' => 'heroicon-o-device-phone-mobile',
                    'Free Money' => 'heroicon-o-device-phone-mobile',
                    'Chèque' => 'heroicon-o-document-text',
                    'Virement' => 'heroicon-o-building-library',
                    'Espèces' => 'heroicon-o-banknotes',
                    default => 'heroicon-o-currency-dollar',
                }), 
                Tables\Columns\TextColumn::make('remittance_date')
                    ->label('Date de versement')
                    ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->translatedFormat('d F Y')) // Traduire le mois
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
