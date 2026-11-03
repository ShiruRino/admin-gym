<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Paid = 'paid';
    case Unpaid = 'unpaid';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Paid => 'Lunas',
            self::Unpaid => 'Belum Lunas',
            self::Cancelled => 'Dibatalkan',
        };
    }
}
