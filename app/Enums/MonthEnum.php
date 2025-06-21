<?php

namespace App\Enums;

enum MonthEnum: int
{
    case JANVIER = 1;
    case FEVRIER = 2;
    case MARS = 3;
    case AVRIL = 4;
    case MAI = 5;
    case JUIN = 6;
    case JUILLET = 7;
    case AOUT = 8;
    case SEPTEMBRE = 9;
    case OCTOBRE = 10;
    case NOVEMBRE = 11;
    case DECEMBRE = 12;

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->name])
            ->toArray();
    }

    public function getLabel(): string
    {
        return match($this) {
            self::JANVIER => 'Janvier',
            self::FEVRIER => 'Février',
            self::MARS => 'Mars',
            self::AVRIL => 'Avril',
            self::MAI => 'Mai',
            self::JUIN => 'Juin',
            self::JUILLET => 'Juillet',
            self::AOUT => 'Août',
            self::SEPTEMBRE => 'Septembre',
            self::OCTOBRE => 'Octobre',
            self::NOVEMBRE => 'Novembre',
            self::DECEMBRE => 'Décembre',
        };
    }

    public function getShortLabel(): string
    {
        return match($this) {
            self::JANVIER => 'Jan',
            self::FEVRIER => 'Fév',
            self::MARS => 'Mar',
            self::AVRIL => 'Avr',
            self::MAI => 'Mai',
            self::JUIN => 'Jun',
            self::JUILLET => 'Jul',
            self::AOUT => 'Aoû',
            self::SEPTEMBRE => 'Sep',
            self::OCTOBRE => 'Oct',
            self::NOVEMBRE => 'Nov',
            self::DECEMBRE => 'Déc',
        };
    }

    public static function getCurrentMonth(): self
    {
        return self::from(now()->month);
    }

    public function getNumber(): int
    {
        return $this->value;
    }
}
