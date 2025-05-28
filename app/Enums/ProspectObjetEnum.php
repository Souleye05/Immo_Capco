<?php

// app/Enums/ProspectObjetEnum.php
namespace App\Enums;

enum ProspectObjetEnum: string
{
    case LOCATION = 'location';
    case VENTE = 'vente';
    case ACHAT = 'achat';
    case GERANCE = 'gerance';

    public function label(): string
    {
        return match($this) {
            self::LOCATION => 'LOCATION',
            self::VENTE => 'VENTE',
            self::ACHAT => 'ACHAT',
            self::GERANCE => 'GÉRANCE',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }
}