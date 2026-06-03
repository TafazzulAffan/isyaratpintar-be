<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetLessonsByMataPelajaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mataPelajaran' => ['required', 'integer', 'min:1'],
        ];
    }

    public function validationData(): array
    {
        return array_merge($this->all(), [
            'mataPelajaran' => $this->route('mataPelajaran'),
        ]);
    }
}
