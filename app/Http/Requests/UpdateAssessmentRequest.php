<?php

namespace App\Http\Requests;

use App\Rules\KelasAccessibleByGuru;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAssessmentRequest extends FormRequest
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
            'slug' => 'sometimes|required|string|unique:assessments,slug,' . $this->route('id'),
            'kelas_id' => ['sometimes', 'integer', 'exists:kelas,id', new KelasAccessibleByGuru()],
            'mata_pelajaran_id' => 'sometimes|integer|exists:mata_pelajarans,id',
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'time_limit' => 'sometimes|required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'slug.unique' => 'Slug assessment sudah digunakan',
            'time_limit.min' => 'Waktu minimal harus 1 menit',
        ];
    }
}
