<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentUpdate extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('documents')->ignore($this->document)->where(function ($query) {
                $query->whereNull('deleted_at');
            })],
            'document_type' => ['required', Rule::in(array_keys(\App\Models\Document::TYPE_LABELS))],
            'priority' => ['required', Rule::in(array_keys(\App\Models\Document::PRIORITY_LABELS))],
            'summary' => ['nullable', 'string', 'max:3000'],
            'external_partner' => ['nullable', 'string', 'max:255'],
            'file' => ['nullable', 'file', 'mimes:docx,pdf,pptx,csv,txt,xlsx,xls'],
            'category_id' => ['required', 'exists:categories,id'],
            'approver_id' => ['nullable', 'exists:users,id'],
            'visibility' => ['nullable', 'boolean'],
            'due_at' => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'visibility' => $this->boolean('visibility'),
        ]);
    }
}
