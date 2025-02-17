<?php

namespace App\Enums;

enum RoleEnum
{
    case Patient;
    case Doctor;

    public function value(): string
    {
        return match ($this) {
            self::Patient => 'patient',
            self::Doctor => 'doctor',
        };
    }
}
