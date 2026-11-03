<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Receptionist = 'receptionist';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Receptionist => 'Resepsionis',
        };
    }
}
