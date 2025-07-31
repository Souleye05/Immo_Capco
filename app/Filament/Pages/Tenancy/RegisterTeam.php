<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\Agency;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\RegisterTenant;
use Illuminate\Support\Str;

class RegisterTeam extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Select agency';
    }

    public function form(Form $form): Form
    {
        // Si l'utilisateur est un owner, il sélectionne parmi ses agences
        if (auth()->user()->hasRole('owner')) {
            return $form
                ->schema([
                    Select::make('agency_id')
                        ->label('Select Agency')
                        ->options(auth()->user()->agencys()->pluck('name', 'agencies.id'))
                        ->required()
                        ->helperText('Select the agency you want to access'),
                ]);
        }

        // Pour les admins, ils peuvent créer de nouvelles agences
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, $state, callable $set) {
                        if ($operation !== 'create') {
                            return;
                        }
                        $set('slug', Str::slug($state));
                    }),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(Agency::class, 'slug')
                    ->rules(['alpha_dash']),
            ]);
    }

    protected function handleRegistration(array $data): Agency
    {
        // Si l'utilisateur est un owner, on récupère l'agence existante
        if (auth()->user()->hasRole('owner') && isset($data['agency_id'])) {
            return Agency::find($data['agency_id']);
        }

        // Pour les admins, on crée une nouvelle agence
        $team = Agency::create($data);
        $team->members()->attach(auth()->user());

        return $team;
    }
}
