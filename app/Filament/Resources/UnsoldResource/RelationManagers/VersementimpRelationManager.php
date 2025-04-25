<?php

namespace App\Filament\Resources\UnsoldResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;

class VersementimpRelationManager extends RelationManager
{
    protected static string $relationship = 'versementimp';

    protected static ?string $recordTitleAttribute = 'reference';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('reference')
                    ->default('IM-' . random_int(100000, 999999))
                    ->disabled()
                    ->dehydrated(true)
                    ->required(),
                    
                TextInput::make('amount')
                    ->label('Montant')
                    ->numeric()
                    ->required(),
                    
                DatePicker::make('versement_date')
                    ->label('Date du paiement')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('unsold.reference')
                    ->label('Référence Impayée')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('reference')
                    ->label('Référence Versement')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant')
                    ->money('XOF')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('versement_date')
                    ->label('Date de paiement')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Créer un versement')
                    ->after(function ($data, $record) {
                        // Calcul du montant total des versements pour cet impayé
                        $verset = DB::table('versementimps')
                            ->where('unsold_id', $record->unsold_id)
                            ->sum('amount');
                        
                        // Récupération du montant total de l'impayé
                        $impaye = DB::table('unsolds')
                            ->where('id', $record->unsold_id)
                            ->value('amount');
                        
                        // Si le total des versements est égal au montant de l'impayé, mettre à jour le statut
                        if ($verset >= $impaye) {
                            DB::table('unsolds')
                                ->where('id', $record->unsold_id)
                                ->update(['status' => true]);
                                
                            Notification::make()
                                ->success()
                                ->title('Impayé réglé intégralement')
                                ->body("Le statut de l'impayé a été mis à jour.")
                                ->send();
                        } else {
                            Notification::make()
                                ->warning()
                                ->title('Impayé partiellement réglé')
                                ->body('Il reste ' . ($impaye - $verset) . ' XOF à régler.')
                                ->send();
                        }
                        
                        $this->getOwnerRecord()->refresh();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(function ($data, $record) {
                        // Même logique que dans le createAction
                        $verset = DB::table('versementimps')
                            ->where('unsold_id', $record->unsold_id)
                            ->sum('amount');
                        
                        $impaye = DB::table('unsolds')
                            ->where('id', $record->unsold_id)
                            ->value('amount');
                        
                        if ($verset >= $impaye) {
                            DB::table('unsolds')
                                ->where('id', $record->unsold_id)
                                ->update(['status' => true]);
                                
                            Notification::make()
                                ->success()
                                ->title('Impayé réglé intégralement')
                                ->body("Le statut de l'impayé a été mis à jour.")
                                ->send();
                        }
                        
                        $this->getOwnerRecord()->refresh();
                    }),
                    
                Tables\Actions\DeleteAction::make()
                    ->after(function ($data, $record) {
                        // Recalcul après suppression
                        $verset = DB::table('versementimps')
                            ->where('unsold_id', $record->unsold_id)
                            ->sum('amount');
                        
                        $impaye = DB::table('unsolds')
                            ->where('id', $record->unsold_id)
                            ->value('amount');
                        
                        // Mise à jour du statut en fonction du montant restant
                        $status = ($verset >= $impaye);
                        DB::table('unsolds')
                            ->where('id', $record->unsold_id)
                            ->update(['status' => $status]);
                            
                        $this->getOwnerRecord()->refresh();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->after(function () {
                        // Recalcul après suppression en masse
                        $unsoldId = $this->getOwnerRecord()->id;
                        
                        $verset = DB::table('versementimps')
                            ->where('unsold_id', $unsoldId)
                            ->sum('amount');
                        
                        $impaye = DB::table('unsolds')
                            ->where('id', $unsoldId)
                            ->value('amount');
                        
                        $status = ($verset >= $impaye);
                        DB::table('unsolds')
                            ->where('id', $unsoldId)
                            ->update(['status' => $status]);
                            
                        $this->getOwnerRecord()->refresh();
                    }),
            ]);
    }
}