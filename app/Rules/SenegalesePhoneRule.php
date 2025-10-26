<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SenegalesePhoneRule implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Regex pour numéro sénégalais: commence par +221 ou 221, suivi de 76|77|78|33|70, puis 7 chiffres
        $pattern = '/^(\+221|221)?(76|77|78|33|70)[0-9]{7}$/';

        if (!preg_match($pattern, $value)) {
            $fail('Le numéro de téléphone doit être un numéro sénégalais valide.');
        }
    }
}