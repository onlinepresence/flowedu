<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Legacy includes/form-validation.php is_valid_ghana_card_number().
 */
final class GhanaCardNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail(__('The :attribute must be a valid Ghana Card number.', ['attribute' => $attribute]));

            return;
        }

        $pattern = '/^GHA-\d{9}-\d{1}$/';

        if (preg_match($pattern, $value) !== 1) {
            $fail(__('The :attribute must match the format GHA-XXXXXXXXX-X.', ['attribute' => $attribute]));
        }
    }
}
