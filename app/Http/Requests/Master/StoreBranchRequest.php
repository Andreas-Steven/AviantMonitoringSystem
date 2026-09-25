<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('branch.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'branch_code' => ['required', 'string', 'max:255', 'unique:branches,branch_code'],
            'branch_name' => ['required', 'string', 'max:255'],
            'branch_type_code' => ['required', 'string', 'exists:branch_types,branch_type_code'],
            'active' => ['required', 'boolean'],
            'opened_date' => ['nullable', 'date'],
            'closed_date' => ['nullable', 'date', 'after_or_equal:opened_date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}