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
            ->recordTitleAttribute('tenant_id')
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name'),
                Tables\Columns\TextColumn::make('amount'),
                Tables\Columns\TextColumn::make('motif'),
                Tables\Columns\BooleanColumn::make('etat'),
                Tables\Columns\TextColumn::make('date'),
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
