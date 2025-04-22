<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\EtatDesFacturesChart;
use App\Filament\Widgets\KpiStats;
use App\Filament\Widgets\RevenusChart;
use App\Filament\Widgets\RevenusVsDepensesChart;
use App\Filament\Widgets\TypesDeBiensChart;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Widgets;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends \Filament\Pages\Dashboard
{
    // ...
    use HasFiltersForm;

    protected static string $routePath = '/';
    

    public function filtersForm(Form $form): Form
    {
        return $form->schema([
            // ...
            Select::make('status')->options(['open', 'closed', 'in_progress'])->default('open'),
            TextInput::make('title'),
            DatePicker::make('created_at'),
            // Toggle::class('active'),
        ]);
    }
    // protected function getHeaderWidgets(): array
    // {
    //     return [
    //         // ...
    //         KpiStats::class,

    //     ];
    // }

}