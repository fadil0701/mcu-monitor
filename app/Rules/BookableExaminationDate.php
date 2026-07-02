<?php

namespace App\Rules;

use App\Support\McuExaminationCalendar;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class BookableExaminationDate implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        $reason = McuExaminationCalendar::unbookableReason($value);

        if ($reason !== null) {
            $fail($reason);
        }
    }
}
