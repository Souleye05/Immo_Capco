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
        return $this === self::LOYER || $this === self::COMMISSION;
    }

    public function getMonthLabel(): string
    {
        return match($this) {
            self::LOYER => 'Mois du loyer',
            self::COMMISSION => 'Mois de la commission',
            self::CAUTION => 'Mois', // Non utilisé
        };
    }
    public function getQuittanceLabel(): string
    {
        return match($this) {
            self::LOYER => 'LOYER',
            self::CAUTION => 'CAUTION',
            self::COMMISSION => 'COMMISSION',
            default => 'Facture',
            
        };
    }

    public function getLabelLignePrincipale(): string
{
    return match ($this) {
        self::LOYER => 'Loyer pour la période',
        self::CAUTION => 'Montant de la caution',
        self::COMMISSION => 'Frais de commission',
        default => 'Montant dû',
    };
}

public function getLabelPaiementEffectue(): string
{
    return match ($this) {
        self::LOYER => 'Paiement effectué',
        self::CAUTION => 'Caution versée',
        self::COMMISSION => 'Commission réglée',
        default => 'Montant payé',
    };
}

}
