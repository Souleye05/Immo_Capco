<?php

namespace App\Enums;

enum PropertyType: string
{
    case IMMEUBLE = 'Immeuble';
    case VILLA = 'Villa';
    case COMMERCE = 'Commerce';
    case TERRAIN = 'Terrain';

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->value])
            ->toArray();
    }

    public static function getLabels(): array
    {
        return [
            self::IMMEUBLE->value => 'Immeuble',
            self::VILLA->value => 'Villa',
            self::COMMERCE->value => 'Commerce',
            self::TERRAIN->value => 'Terrain',
        ];
    }

    public function getLabel(): string
    {
        return match($this) {
            self::IMMEUBLE => 'Immeuble',
            self::VILLA => 'Villa',
            self::COMMERCE => 'Commerce',
            self::TERRAIN => 'Terrain',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::IMMEUBLE => 'heroicon-o-building-office',
            self::VILLA => 'heroicon-o-home',
            self::COMMERCE => 'heroicon-o-building-storefront',
            self::TERRAIN => 'heroicon-o-map',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::IMMEUBLE => 'primary',
            self::VILLA => 'success',
            self::COMMERCE => 'warning',
            self::TERRAIN => 'info',
        };
    }
}
