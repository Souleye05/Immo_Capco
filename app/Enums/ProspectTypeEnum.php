<?php

namespace App\Enums;

enum ProspectTypeEnum: string
{
    case STUDIO = 'studio';
    case F2 = 'f2';
    case F3 = 'f3';
    case F4 = 'f4';
    case FIVE_PLUS = '5+';

    public function label(): string
    {
        return match($this) {
            self::STUDIO => 'Studio',
            self::F2 => 'F2',
            self::F3 => 'F3',
            self::F4 => 'F4',
            self::FIVE_PLUS => '5 et +',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }
}