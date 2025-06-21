<?php

namespace App\Enums;

enum FlatType: string
{
    case STUDIO = 'studio';
    case F1 = 'f1';
    case F2 = 'f2';
    case F3 = 'f3';
    case F4 = 'f4';
    case F5 = 'f5';
    case F6_PLUS = 'f6+';

    public function label(): string
    {
        return match($this) {
            self::STUDIO => 'Studio',
            self::F1 => 'F1',
            self::F2 => 'F2',
            self::F3 => 'F3',
            self::F4 => 'F4',
            self::F5 => 'F5',
            self::F6_PLUS => 'F6+',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }
}