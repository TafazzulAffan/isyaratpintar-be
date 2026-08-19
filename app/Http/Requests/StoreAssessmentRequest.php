<?php

namespace App\Http\Requests;

use App\Rules\KelasAccessibleByGuru;
use Illuminate\Foundation\Http\FormRequest;

class StoreAssessmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'slug' => 'required|string|unique:assessments,slug',
            'kelas_id' => ['required', 'integer', 'exists:kelas,id', new KelasAccessibleByGuru()],
            'mata_pelajaran_id' => 'required|integer|exists:mata_pelajarans,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'time_limit' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'slug.unique' => 'Slug assessment sudah digunakan',
            'mata_pelajaran_id.required' => 'Mata pelajaran harus dipilih',
            'mata_pelajaran_id.exists' => 'Mata pelajaran tidak ditemukan',
            'time_limit.min' => 'Waktu minimal harus 1 menit',
        ];
    }
}
