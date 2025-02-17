<?php

namespace App\Enums;

enum PaymentStatus
{
    case Pending;
    case Completed;
    case Failed;

    public function value(): string
    {
        return match ($this) {
            self::Pending  => 'pending',
            self::Completed => 'completed',
            self::Failed => 'failed',
        };
    }
}
