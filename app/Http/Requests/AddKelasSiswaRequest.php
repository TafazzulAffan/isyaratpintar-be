<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddKelasSiswaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'user_ids' => [
                'required',
                'array',
                'min:1',
            ],
            'user_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', UserRole::SISWA->value)),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'user_ids.required'  => 'Daftar ID siswa wajib diisi.',
            'user_ids.array'     => 'Format data siswa tidak valid.',
            'user_ids.min'       => 'Minimal harus ada 1 siswa yang dipilih.',
            'user_ids.*.integer' => 'Setiap ID siswa harus berupa angka.',
            'user_ids.*.exists'  => 'Satu atau lebih siswa tidak ditemukan atau bukan merupakan siswa.',
        ];
    }
}
