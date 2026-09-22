<?php

namespace App\Enums;

enum StockMovementReason: string
{
    case Purchase = 'purchase';
    case CountCorrection = 'count_correction';
    case Damage = 'damage';
    case Return = 'return';
    case ApiOrder = 'api_order';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Purchase => 'Purchase',
            self::CountCorrection => 'Count correction',
            self::Damage => 'Damage',
            self::Return => 'Return',
            self::ApiOrder => 'API order',
            self::Other => 'Other',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Purchase => 'lime',
            self::CountCorrection => 'amber',
            self::Damage => 'red',
            self::Return => 'blue',
            self::ApiOrder => 'violet',
            self::Other => 'zinc',
        };
    }
}
