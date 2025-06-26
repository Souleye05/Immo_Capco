<?php

namespace App\Filament\Resources\RemittanceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Hidden;
use Filament\Notifications\Notification;
use App\Models\Remittance;
use Closure;

class RemittancePartialRelationManager extends RelationManager
{
    protected static string $relationship = 'remittancePartials';

    protected static ?string $recordTitleAttribute = 'reference';
    
    protected static ?string $title = 'Versements';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('reference')
                    ->label('Référence du paiement')
                    ->required()
                    ->dehydrated()
                    ->disabled()
                    ->default(fn () => 'PAY-' . random_int(100000, 999999)),
                // TextInput::make('amount')
                //     ->label('Montant du versement')
                //     ->numeric()
                //     ->suffix('FCFA')
                //     ->required()
                //     ->reactive()
                //     ->validationAttribute('Montant du versement')
                //     ->afterStateUpdated(function ($state, $record, $livewire) {
                //         // Si c'est une création ou modification, on vérifie contre le montant restant
                //         $remittance = $livewire->getOwnerRecord();
                //         $remaining = $remittance->remaining;
                        
                //         // Si modification, on ajoute le montant actuel du record car il sera soustrait du calcul
                //         if ($record && $record->exists) {
                //             $remaining += $record->amount;
                //         }
                        
                //         if ($state > $remaining) {
                //             throw new \Exception("Le montant entré ({$state} FCFA) dépasse le montant restant à reverser ({$remaining} FCFA).");
                //         }
                //     }),
                TextInput::make('amount')
    ->label('Montant du versement')
    ->numeric()
    ->suffix('FCFA')
    ->required()
    ->reactive()
    ->rule(function (callable $get) {
        // Récupérer $this ici référencera le RelationManager
        $remittance = $this->getOwnerRecord(); // pas besoin de passer par $get('livewire')
        $remaining = $remittance->remaining;
    
        if ($record = $get('record')) {
            $remaining += $record->amount;
        }
    
        return function (string $attribute, $value, Closure $fail) use ($remaining) {
            if ($value > $remaining) {
                $fail("Le montant entré ({$value} FCFA) dépasse le montant restant à reverser ({$remaining} FCFA).");
            }
        };
    }),
    


                Select::make('mode_remit')
                    ->label('Mode de versement')
                    ->options([
                        'OM' => 'OM',
                        'Wave' => 'Wave',
                        'Free Money' => 'Free Money',
                        'Chèque' => 'Chèque',
                        'Virement' => 'Virement',
                        'Espèces' => 'Espèces',
                    ])
                    ->required(),

                DatePicker::make('remittance_date')
                    ->label('Date de versement')
                    ->default(now())
                    ->required(),

                Hidden::make('current_month')
                    ->default(fn ($livewire) => $livewire->getOwnerRecord()->current_month),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant versé')
                    ->money('XOF')
                    ->sortable(),
                    
                    Tables\Columns\TextColumn::make('mode_remit')
                    ->label('Mode de versement')
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
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->after(function ($data, $record, $livewire) {
                        // Mettre à jour le montant restant et le statut de la remittance
                        $this->updateRemittanceAfterChange($livewire);
                        
                        // Notification pour confirmer le versement
                        Notification::make()
                            ->title('Versement enregistré')
                            ->body('Le versement a été ajouté avec succès')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(function ($data, $record, $livewire) {
                        // Mettre à jour le montant restant et le statut de la remittance
                        $this->updateRemittanceAfterChange($livewire);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function ($data, $record, $livewire) {
                        // Mettre à jour le montant restant et le statut de la remittance
                        $this->updateRemittanceAfterChange($livewire);
                    }),
                Tables\Actions\ViewAction::make('Télécharger quittance')
                    ->icon('heroicon-o-document-text')
                    ->label('Quittance')
                    ->url(fn ($record) => $record->receipt_path ? Storage::url($record->receipt_path) : '#')
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => !empty($record->receipt_path)),
                
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function ($livewire) {
                            // Mettre à jour le montant restant et le statut de la remittance
                            $this->updateRemittanceAfterChange($livewire);
                        }),
                ]),
            ]);
    }
    
    // Méthode pour mettre à jour la remittance après une action sur un paiement partiel
    protected function updateRemittanceAfterChange($livewire)
    {
        $remittance = $livewire->getOwnerRecord();
        
        // Recalcul des montants
        $totalPartialPayments = $remittance->remittancePartials()->sum('amount');
        $newRemaining = $remittance->amount_to_transfer - $totalPartialPayments;
        
        // Détermination du nouveau statut
        if ($totalPartialPayments == 0) {
            $newStatus = 'Pending';
        } elseif ($newRemaining == 0) {
            $newStatus = 'Paid';
        } else {
            $newStatus = 'Partial';
        }
        
        // Mise à jour de la remittance
        $remittance->update([
            'amount' => $totalPartialPayments, // Montant total déjà versé
            'remaining' => $newRemaining,      // Montant restant à verser
            'status' => $newStatus             // Nouveau statut
        ]);
    }
}