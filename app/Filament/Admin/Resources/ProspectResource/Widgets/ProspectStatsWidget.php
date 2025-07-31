<?php

// app/Filament/Resources/ProspectResource/Widgets/ProspectStatsWidget.php
namespace App\Filament\Admin\Resources\ProspectResource\Widgets;

use App\Enums\ProspectObjetEnum;
use App\Enums\ProspectTypeEnum;
use App\Models\Prospect;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class ProspectStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalProspects = Prospect::count();
        // $interestedProspects = Prospect::interested()->count();
        $recentProspects = Prospect::where('created_at', '>=', now()->subDays(7))->count();
        
        // Top secteur
        $topSecteur = Prospect::select('secteur_localisation')
            ->whereNotNull('secteur_localisation')
            ->groupBy('secteur_localisation')
            ->orderByRaw('COUNT(*) DESC')
            ->first();

        // Objet le plus recherché
        $objetStats = $this->getObjetStats();
        $topObjet = $objetStats['top'] ?? 'N/A';

        return [
            Stat::make('Total Prospects', $totalProspects)
                ->description('Nombre total de prospects')
                ->descriptionIcon('heroicon-o-users')
                ->color('primary'),
            
            // Stat::make('Nouveaux (7j)', $recentProspects)
            //     ->description('Prospects récents')
            //     ->descriptionIcon('heroicon-o-clock')
            //     ->color('info'),
            
            Stat::make('Secteur populaire', $topSecteur?->secteur_localisation ?? 'N/A')
                ->description('Secteur le plus demandé')
                ->descriptionIcon('heroicon-o-map-pin')
                ->color('warning'),
            
            Stat::make('Objet top', $topObjet)
                ->description('Objet le plus recherché')
                ->descriptionIcon('heroicon-o-building-office')
                ->color('gray'),

        // stat pour le type le plus recherché
            Stat::make('Type populaire', $this->getTypeStats()['top'])
                ->description('Type de bien le plus recherché')
                ->descriptionIcon('heroicon-o-home')
                ->color('success'),


        ];
    }

    // private function getWeeklyInterestChart(): array
    // {
    //     $data = [];
    //     for ($i = 6; $i >= 0; $i--) {
    //         $date = now()->subDays($i)->startOfDay();
    //         $count = Prospect::interested()
    //             ->whereDate('created_at', $date)
    //             ->count();
    //         $data[] = $count;
    //     }
    //     return $data;
    // }

    private function getObjetStats(): array
    {
        $objetCounts = [];
        
        foreach (ProspectObjetEnum::cases() as $objet) {
            $count = Prospect::byObjet($objet)->count();
            $objetCounts[$objet->label()] = $count;
        }
        
        arsort($objetCounts);
        
        return [
            'top' => array_key_first($objetCounts) ?? 'N/A',
            'all' => $objetCounts
        ];
    }
    private function getTypeStats(): array
    {
        $typeCounts = [];
        foreach (ProspectTypeEnum::cases() as $type) {
            $count = Prospect::byType($type)->count();
            $typeCounts[$type->label()] = $count;
        }
        arsort($typeCounts);
        return [
            'top' => array_key_first($typeCounts) ?? 'N/A',
            'all' => $typeCounts
        ];
    }
}