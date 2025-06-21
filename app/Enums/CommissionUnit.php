<?php

namespace App\Enums;

enum CommissionUnit: string
{
    case PERCENTAGE = '%';
    case FCFA = 'F CFA';

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->value])
            ->toArray();
    }

    public function getLabel(): string
    {
        return match($this) {
            self::PERCENTAGE => '%',
            self::FCFA => 'F CFA',
        };
    }

    public function getSymbol(): string
    {
        return $this->value;
    }
}