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
                // ->dehydrated(true)
                ->required(),

            Select::make('tenant_id')
                ->label('Locataire')
                ->options(Tenant::all()->pluck('name', 'id')->toArray())
                ->searchable()
                ->reactive()
                ->createOptionForm([
                    TextInput::make('name')
                        ->label('Nom & Prénoms du locataire')
                        ->required(),
                    TextInput::make('phone')
                        ->label('Téléphone')
                        ->tel()
                        ->required(),
                    TextInput::make('address')
                        ->label('Adresse')
                        ->required(),
                    // Select::make('flat_id')
                    //     ->label('Appartement')
                    //     ->relationship('flat', 'reference')
                    //     ->searchable()
                    //     ->required(),
                ])
                ->createOptionUsing(function ($data) {
                    $tenant = Tenant::create([
                        'name' => $data['name'],
                        'phone' => $data['phone'],
                        'address' => $data['address'],
                        // 'flat_id' => $data['flat_id'],
                    ]);

                    return $tenant->id;
                })
                ->createOptionAction(function ($action) {
                    return $action
                        ->modalHeading('Créer un nouveau locataire')
                        ->modalButton('Créer locataire')
                        ->modalWidth('lg');
                })
                ->afterStateUpdated(function ($state, callable $set) {
                    if ($state) {
                        // Pour la facture (payment_id)
                        $set('payment_id', Payment::find($state)?->numero ?? 0);
                        
                        // Pour le montant, si vous voulez récupérer le loyer du locataire
                        $tenant = Tenant::find($state);
                        if ($tenant && $tenant->flat_id) {
                            $flat = Flat::find($tenant->flat_id);
                            if ($flat) {
                                $set('amount', $flat->loyer);
                            }
                        }
                    }
                }),
                    
                TextInput::make('amount'),
                Textarea::make('motif')
                    ->maxLength(65535),
                Toggle::make('etat')
                    ->disabled(),
                DatePicker::make('date'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Locataire')
                    ->searchable()
                    ->sortable(),
                // Tables\Columns\TextColumn::make('payment_id')
                //     ->label('Numéro de facture')
                //     ->searchable()
                //     ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant')
                    ->money('XOF')
                    ->sortable(),
                Tables\Columns\TextColumn::make('motif')
                    ->label('Motif')
                    ->limit(50)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('etat')
                    ->label('État')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Mis à jour le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
          
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
