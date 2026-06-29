<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class TahunAjaranBerurutan implements ValidationRule
{
    /**
     * Validate that the end year is exactly one year after the start year.
     *
     * @param  Closure(string, ?string=): void  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^(\d{4})\/(\d{4})$/', $value, $matches)) {
            return;
        }

        if ((int) $matches[2] !== ((int) $matches[1] + 1)) {
            $fail('Tahun akhir harus tepat satu tahun setelah tahun awal.');
        }
    }
}
