<?php

namespace App\Rules;

use App\Support\AuthEmail;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class GmailAddress implements ValidationRule
{
    public function __construct(private ?int $ignoreId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/\A[a-z0-9]+(?:\.[a-z0-9]+)*(?:\+[a-z0-9._-]+)?@gmail\.com\z/i', trim($value))) {
            $fail('Enter a valid Gmail address ending in @gmail.com.');

            return;
        }

        if (AuthEmail::isTaken($value, $this->ignoreId)) {
            $fail('This Gmail address is already registered.');
        }
    }
}
