<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Transfer = 'transfer';
    case Qris = 'qris';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Tunai (Cash)',
            self::Transfer => 'Transfer Bank',
            self::Qris => 'QRIS',
        };
    }
}
