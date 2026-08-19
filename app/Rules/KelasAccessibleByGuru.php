<?php

namespace App\Rules;

use App\Models\Kelas;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class KelasAccessibleByGuru implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = auth()->user();

        if (!$user) {
            $fail('Unauthorized.');
            return;
        }

        if ($user->isAdmin()) {
            return;
        }

        $kelas = Kelas::find($value);

        if (!$kelas || $kelas->guru_id !== $user->id) {
            $fail('Kelas tidak ditemukan atau Anda tidak memiliki akses.');
        }
    }
}
