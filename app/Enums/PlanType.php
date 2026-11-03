<?php

namespace App\Enums;

enum PlanType: string
{
    case Membership = 'membership';
    case PtSession = 'pt_session';
    case Bundle = 'bundle';

    public function label(): string
    {
        return match ($this) {
            self::Membership => 'Membership Gym',
            self::PtSession => 'Personal Trainer (PT)',
            self::Bundle => 'Bundling (Membership + PT)',
        };
    }
}
