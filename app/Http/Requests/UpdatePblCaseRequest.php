<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePblCaseRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert empty string image_url to null
        if ($this->image_url === '') {
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
        $pblCase = $this->route('pblCase') ?? $this->route('pbl_case');
        
        return [
            'title' => 'sometimes|string|max:255',
            'mata_pelajaran_id' => 'sometimes|integer|exists:mata_pelajarans,id',
            'description' => 'sometimes|string',
            'image_url' => 'nullable|url',
            'time_limit' => 'nullable|integer|min:0',
            'start_date' => 'sometimes|date_format:Y-m-d H:i:s',
            'deadline' => 'sometimes|date_format:Y-m-d H:i:s|after:start_date',
        ];
    }
}
