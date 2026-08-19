<?php

namespace App\Http\Requests;

use App\Rules\KelasAccessibleByGuru;
use Illuminate\Foundation\Http\FormRequest;

class StorePblCaseRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert blank/whitespace-only image_url to null
        if ($this->has('image_url') && trim((string) $this->input('image_url')) === '') {
            $this->merge(['image_url' => null]);
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $userRole = auth()->user()->role->value ?? auth()->user()->role;
        return auth()->check() && in_array($userRole, ['admin', 'guru']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'case_number' => 'required|integer|unique:pbl_cases',
            'title' => 'required|string|max:255',
            'kelas_id' => ['required', 'integer', 'exists:kelas,id', new KelasAccessibleByGuru()],
            'mata_pelajaran_id' => 'required|integer|exists:mata_pelajarans,id',
            'description' => 'required|string',
            'image_url' => 'nullable|url',
            'time_limit' => 'nullable|integer|min:0',
            'start_date' => 'required|date_format:Y-m-d H:i:s|after_or_equal:now',
            'deadline' => 'required|date_format:Y-m-d H:i:s|after:start_date',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'case_number.unique' => 'Case number already exists',
            'deadline.after' => 'Deadline must be after start date',
        ];
    }
}
