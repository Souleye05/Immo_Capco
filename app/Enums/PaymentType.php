<?php

namespace App\Enums;

enum PaymentType: string
{
    //$
    case LOYER = 'Loyer';
    case CAUTION = 'Caution';
    case COMMISSION = 'Commission';

    public function getLabel(): string
    {
        return match ($this) {
            self::LOYER => 'Loyer',
            self::CAUTION => 'Caution',
            self::COMMISSION => 'Commission',
        };
    }

    public function getPrefix(): string
    {
        return match ($this) {
            self::LOYER => 'LOY',
            self::CAUTION => 'CAU',
            self::COMMISSION => 'COM',
        };
    }

   public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }

    public function getDescription(): string
    {
        return match($this) {
            self::LOYER => 'Paiement mensuel du loyer',
            self::CAUTION => 'Garantie versée en début de bail',
            self::COMMISSION => 'Commission sur la gestion du bien',
        };
    }
public function requiresMonth(): bool
    {
        return $this === self::LOYER;
    }
}
