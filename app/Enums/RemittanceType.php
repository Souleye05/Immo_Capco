<?php

namespace App\Enums;

enum RemittanceType: string
{
    case LOYER = 'loyer';
    case CAUTION = 'caution';

    public function label(): string
    {
        return match ($this) {
            self::LOYER => 'Loyer',
            self::CAUTION => 'Caution',
        };
    }

    public function getPrefix(): string
    {
        return match ($this) {
            self::LOYER => 'REM-LOY',
            self::CAUTION => 'REM-CAU',
        };
    }
    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }
    
}
