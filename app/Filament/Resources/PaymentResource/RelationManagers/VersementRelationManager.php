<?php

namespace App\Filament\Resources\PaymentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use App\Models\Unsold; // Ajout de l'import du modèle Unsold

class VersementRelationManager extends RelationManager
{
    protected static string $relationship = 'versement';

    protected static ?string $recordTitleAttribute = 'payment_id';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('reference')
                    ->label('Référence du versement')
                    ->default('VER-' . random_int(100000, 999999))
                    ->disabled()
                    ->dehydrated(true)
                    ->required(),

                Forms\Components\TextInput::make('amount')
                    ->label('Montant')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                
                Select::make('payment_method')
                    ->label('Méthode de paiement')
                    ->searchable()
                    ->options([
                        'OM' => 'OM',
                        'Wave' => 'Wave',
                        'Free Money' => 'Free Money',
                        'Chèque' => 'Chèque',
                        'Virement' => 'Vrirement',
                        'Espèces' => 'Espèces',
                    ]),

                DatePicker::make('versement_date')
                    ->label('Date du paiement')
                    ->default(now())
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        $url = url()->current();
        $replace = str_replace("/edit", "", $url);
        $pos = strpos($replace, '/', 0);
        $last5 = substr($replace, 37);
        
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference'),
                Tables\Columns\TextColumn::make('payment.numero'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant versé')
                    ->alignCenter()
                    ->formatStateUsing(fn ($state): string => number_format($state, 0, ',', ' ') . ' FCFA')
                    ->searchable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Mode de versement')
                    ->alignCenter()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
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
                Tables\Columns\TextColumn::make('versement_date')
                    ->label('Date de paiement')
                    ->alignCenter()
                    ->date(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make('payment_id')
                    ->label('Créer un versement')
                    ->before(function (array $data, RelationManager $livewire, Tables\Actions\CreateAction $action) {
                        // Vérifier si le montant est positif
                        if ($data['amount'] <= 0) {
                            Notification::make()
                                ->warning()
                                ->title('Montant invalide')
                                ->body('Le montant du versement doit être supérieur à 0')
                                ->send();

                            // Arrêter la création du versement
                            $action->cancel();
                            return false;
                        }
                        
                        // Récupérer la facture associée
                        $payment = DB::table('payments')
                            ->where('id', $livewire->ownerRecord->id)
                            ->first();
                            
                        // Vérifier si la facture est déjà payée
                        if ($payment->status == 1) {
                            Notification::make()
                                ->warning()
                                ->title('Opération impossible')
                                ->body('Cette facture a déjà été réglée.')
                                ->send();

                            // Arrêter la création du versement
                            $action->cancel();
                            return false;
                        } 
                         
                        // Calculer la somme des versements existants
                        $verset = DB::table('versements')
                            ->where('payment_id', $livewire->ownerRecord->id)
                            ->sum('amount');

                        // Vérifier si le montant du versement dépasse le montant de la facture
                        if ($verset + $data['amount'] > $payment->amount) {
                            Notification::make()
                                ->warning()
                                ->title('Montant invalide')
                                ->body('Le total des versements dépasserait le montant dû. Montant maximum autorisé: '
                                    . ($payment->amount - $verset) . ' FCFA')
                                ->send();

                            // Arrêter la création du versement
                            $action->cancel();
                            return false;
                        }
                    })
                    ->after(function ($livewire, $record) {
                        // Get the payment record directly first
                        $payment = DB::table('payments')
                            ->where('id', $record['payment_id'])
                            ->first();
                        
                        if (!$payment) {
                            Notification::make()
                                ->error()
                                ->title('Erreur')
                                ->body('Paiement non trouvé')
                                ->send();
                            return;
                        }
                        
                        // Get the sum of all versements for this payment
                        $totalVersements = DB::table('versements')
                            ->where('payment_id', $record['payment_id'])
                            ->sum('amount');
                        
                        // Compare the total versements with the payment amount
                        if ($totalVersements >= $payment->amount) {
                            // Récupérer les impayés avant de les supprimer
                            $unsolds = Unsold::where('tenant_id', $payment->tenant_id)
                                ->withTrashed(false)  // Seulement les non-supprimés
                                ->get();
                            
                            // Si des impayés existent, les stocker et afficher une notification
                            if ($unsolds->count() > 0) {
                                // Créer un tableau pour stocker les détails des impayés
                                $unsoldDetails = [];
                                $totalUnsold = 0;
                                
                                foreach ($unsolds as $unsold) {
                                    $unsoldDetails[] = [
                                        'id' => $unsold->id,
                                        'montant' => $unsold->amount,
                                        'motif' => $unsold->reason,
                                        'date' => $unsold->due_date,
                                    ];
                                    $totalUnsold += $unsold->amount;
                                    
                                    // Marquer l'impayé comme payé (sans le supprimer)
                                    // Optionnel: créer un champ 'paid_at' dans la table unsolds
                                    $unsold->paid_at = now();
                                    $unsold->save();
                                    
                                    // Soft delete l'impayé
                                    $unsold->delete();
                                }
                                
                                // Stocker les détails des impayés réglés dans une table de logs ou d'historique
                                DB::table('unsold_payments_history')->insert([
                                    'tenant_id' => $payment->tenant_id,
                                    'payment_id' => $payment->id,
                                    'total_amount' => $totalUnsold,
                                    'details' => json_encode($unsoldDetails),
                                    'cleared_at' => now(),
                                ]);
                                
                                // Notification pour les impayés réglés
                                Notification::make()
                                    ->success()
                                    ->title('Impayés réglés')
                                    ->body("Tous les impayés du locataire ont été réglés, montant total: " . 
                                          number_format($totalUnsold, 0, ',', ' ') . " FCFA")
                                    ->send();
                            }
                            
                            // Update the payment status to complete (1)
                            DB::table('payments')
                                ->where('id', $record['payment_id'])
                                ->update(['status' => 1]);
                            
                            Notification::make()
                                ->success()
                                ->title('Loyer payé')
                                ->body("Le locataire a payé son loyer. Rafraîchissez la page s'il vous plaît.")
                                ->send();
                        } else {
                            Notification::make()
                                ->warning()
                                ->title('Facture du mois non payé')
                                ->body('Ce locataire a des impayés')
                                ->send();
                        }
                        // Add this to refresh the page after processing
                        $livewire->dispatch('refresh');
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                ->after(function ($record, $livewire) {
                    // Récupérer le paiement associé
                    $payment = DB::table('payments')
                        ->where('id', $record->payment_id)
                        ->first();
                    
                    if (!$payment) {
                        Notification::make()
                            ->error()
                            ->title('Erreur')
                            ->body('Paiement non trouvé')
                            ->send();
                        return;
                    }
                    
                    // Recalculer le total des versements restants après la suppression
                    $totalVersements = DB::table('versements')
                        ->where('payment_id', $record->payment_id)
                        ->sum('amount');
    
                    // Mettre à jour le statut en fonction du total des versements
                    if ($totalVersements < $payment->amount) {
                        // Si le total est inférieur au montant dû, marquer comme impayé (0)
                        DB::table('payments')
                            ->where('id', $record->payment_id)
                            ->update(['status' => 0]);
                        
                        Notification::make()
                            ->warning()
                            ->title('Statut mis à jour')
                            ->body('Le statut de la facture a été mis à jour en impayé')
                            ->send();
                    } else if ($totalVersements >= $payment->amount) {
                        // Si le total est égal ou supérieur au montant dû, vérifier s'il faut conserver le statut payé (1)
                        DB::table('payments')
                            ->where('id', $record->payment_id)
                            ->update(['status' => 1]);
        
                        Notification::make()
                            ->success()
                            ->title('Statut inchangé')
                            ->body('Le montant restant couvre toujours la facture')
                            ->send();
                    }
    
                    // Rafraîchir la page pour afficher les changements
                    $livewire->dispatch('refresh');
                }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                ->after(function ($records, $livewire) {
                    // Pour chaque enregistrement supprimé en masse
                    foreach ($records as $record) {
                        // Récupérer le paiement associé
                        $payment = DB::table('payments')
                            ->where('id', $record->payment_id)
                            ->first();
                        
                        if (!$payment) {
                            continue;
                        }
                        
                        // Recalculer le total des versements restants après la suppression
                        $totalVersements = DB::table('versements')
                            ->where('payment_id', $record->payment_id)
                            ->sum('amount');
                        
                        // Mettre à jour le statut en fonction du total des versements
                        if ($totalVersements < $payment->amount) {
                            DB::table('payments')
                                ->where('id', $record->payment_id)
                                ->update(['status' => 0]);
                                
                        } else if ($totalVersements >= $payment->amount) {
                            DB::table('payments')
                                ->where('id', $record->payment_id)
                                ->update(['status' => 1]);
                        }
                    }
                    
                    Notification::make()
                        ->success()
                        ->title('Statuts mis à jour')
                        ->body('Les statuts des factures ont été mis à jour')
                        ->send();
                        
                    // Rafraîchir la page pour afficher les changements
                    $livewire->dispatch('refresh');
                }),
            ]);
    }
}