<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case OM = 'OM';
    case WAVE = 'Wave';
    case FREE_MONEY = 'Free Money';
    case CHEQUE = 'Chèque';
    case VIREMENT = 'Virement';
    case ESPECES = 'Espèces';

    public function getColor(): string
    {
        return match ($this) {
            self::OM => 'danger',
            self::WAVE => 'info',
            self::FREE_MONEY, self::CHEQUE => 'warning',
            self::VIREMENT => 'info',
            self::ESPECES => 'success',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::OM, self::WAVE, self::FREE_MONEY => 'heroicon-o-device-phone-mobile',
            self::CHEQUE => 'heroicon-o-document-text',
            self::VIREMENT => 'heroicon-o-building-library',
            self::ESPECES => 'heroicon-o-banknotes',
        };
    }

    /**
     * Obtenir le libellé français pour l'affichage
     * 
     * @return string Libellé lisible
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::OM => 'Orange Money',
            self::WAVE => 'Wave',
            self::FREE_MONEY => 'Free Money',
            self::CHEQUE => 'Chèque',
            self::VIREMENT => 'Virement',
            self::ESPECES => 'Espèces',
        };
    }

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->value])
            ->toArray();
    }
}