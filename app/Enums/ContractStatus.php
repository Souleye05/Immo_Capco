<?php

namespace App\Enums;

enum ContractStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case TERMINATED = 'terminated';
    case RENEWED = 'renewed';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Brouillon',
            self::ACTIVE => 'Actif',
            self::EXPIRED => 'Expiré',
            self::TERMINATED => 'Résilié',
            self::RENEWED => 'Renouvelé',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::DRAFT => 'secondary',
            self::ACTIVE => 'success',
            self::EXPIRED => 'warning',
            self::TERMINATED => 'danger',
            self::RENEWED => 'primary',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::DRAFT => 'heroicon-o-pencil',
            self::ACTIVE => 'heroicon-o-check-circle',
            self::EXPIRED => 'heroicon-o-exclamation-triangle',
            self::TERMINATED => 'heroicon-o-x-circle',
            self::RENEWED => 'heroicon-o-arrow-path',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }

    public static function colors(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->color() => $case->value])
            ->toArray();
    }

    public static function icons(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->icon() => $case->value])
            ->toArray();
    }

    public function canBeRenewed(): bool
    {
        return in_array($this, [self::ACTIVE, self::EXPIRED]);
    }

    public function canBeTerminated(): bool
    {
        return $this === self::ACTIVE;
    }
}