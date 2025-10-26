<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SenegaleseNCIRule implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Regex pour NCI sénégalais: 13 chiffres suivis d'une lettre majuscule
        $pattern = '/^[0-9]{13}[A-Z]{1}$/';

        if (!preg_match($pattern, $value)) {
            $fail('Le NCI doit être au format valide (13 chiffres suivis d\'une lettre majuscule).');
        }
    }
}