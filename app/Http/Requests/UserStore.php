<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UserStore extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return
            [
                'name' => ['required', 'string', 'max:255'],
                'position' => ['nullable', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:50'],
                'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($this->user)],
                'password' => ['required', 'confirmed', 'min:8'],
                'role' => ['nullable', 'in:0,1,2,3'],
                'categories' => ['required', 'array', 'min:1'],
            ];
    }
}
