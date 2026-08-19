<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKelasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdminOrGuru() ?? false;
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'guru_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('role', 'guru');
                }),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama kelas wajib diisi.',
            'guru_id.exists' => 'Guru yang dipilih tidak valid atau tidak terdaftar sebagai Guru.',
        ];
    }
}
