<?php

namespace App\Http\Requests;

use App\Rules\KelasAccessibleByGuru;
use Illuminate\Foundation\Http\FormRequest;

class StoreLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'kelas_id' => ['required', 'integer', 'exists:kelas,id', new KelasAccessibleByGuru()],
            'mata_pelajaran_id' => ['required', 'integer', 'exists:mata_pelajarans,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'duration' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'mata_pelajaran_id.required' => 'Mata pelajaran harus dipilih.',
            'mata_pelajaran_id.exists' => 'Mata pelajaran yang dipilih tidak ditemukan.',
        ];
    }

    public function attributes(): array
    {
        return [
            'mata_pelajaran_id' => 'mata pelajaran',
        ];
    }
}
