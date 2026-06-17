<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Legacy includes/form-validation.php is_valid_phone_number() using $phone_prefixes.
 */
final class GhanaMobilePhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail(__('The :attribute must be a valid phone number.', ['attribute' => $attribute]));

            return;
        }

        $digits = preg_replace('/\D/', '', $value);

        if (strlen($digits) !== 10 || ! str_starts_with($digits, '0')) {
            $fail(__('The :attribute must be a 10-digit number starting with 0.', ['attribute' => $attribute]));

            return;
        }

        $prefix = substr($digits, 0, 3);
        $allowed = config('college.ghana_phone_prefixes', []);

        if (! in_array($prefix, $allowed, true)) {
            $fail(__('The :attribute is not a recognised Ghana mobile prefix.', ['attribute' => $attribute]));
        }
    }
}
