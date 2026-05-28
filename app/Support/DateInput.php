<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;

class DateInput
{
    public const DISPLAY_FORMAT = 'd/m/Y';

    public static function toDisplay(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (preg_match('#^\d{1,2}/\d{1,2}/\d{4}$#', $value)) {
            return $value;
        }

        try {
            return Carbon::parse($value)->format(self::DISPLAY_FORMAT);
        } catch (InvalidFormatException) {
            return $value;
        }
    }

    public static function toIso(string $value): string
    {
        if (preg_match('#^\d{4}-\d{2}-\d{2}$#', $value)) {
            return $value;
        }

        return Carbon::createFromFormat(self::DISPLAY_FORMAT, $value)->format('Y-m-d');
    }

    public static function parse(string $value): Carbon
    {
        return Carbon::createFromFormat(self::DISPLAY_FORMAT, $value)->startOfDay();
    }
}
