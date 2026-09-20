<?php

namespace App\Enums;

enum ConditionRecordType: string
{
    case Before = 'before';
    case After = 'after';

    public function label(): string
    {
        return match ($this) {
            self::Before => 'Before Rental',
            self::After => 'After Rental',
        };
    }
}
