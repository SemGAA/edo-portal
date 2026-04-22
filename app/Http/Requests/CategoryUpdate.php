<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryUpdate extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('categories')->ignore($this->category)],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('categories')->ignore($this->category)],
        ];
    }

    /**
     * Get the validation error messages.
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'Такой отдел уже существует.',
            'code.unique' => 'Такой код отдела уже используется.',
        ];
    }
}
