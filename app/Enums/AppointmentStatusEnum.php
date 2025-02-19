<?php

namespace App\Enums;

enum AppointmentStatusEnum
{
    case Pending;
    case Paid;
    case Confirmed;
    case Cancelled;

    public function value(): string
    {
        return match ($this) {
            self::Pending  => 'pending',
            self::Paid => 'paid',
            self::Confirmed => 'confirmed',
            self::Cancelled => 'cancelled',
        };
    }
}
