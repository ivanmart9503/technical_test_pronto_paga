<?php

namespace App\Enums;

enum PaymentStatusEnum
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
