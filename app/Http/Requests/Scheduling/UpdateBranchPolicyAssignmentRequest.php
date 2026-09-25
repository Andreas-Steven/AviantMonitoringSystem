<?php

namespace App\Http\Requests\Scheduling;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBranchPolicyAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('policy.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', 'exists:branches,branch_id'],
            'policy_id' => ['required', 'integer', 'exists:attendance_policies,policy_id'],
            'effective_start_date' => ['required', 'date'],
            'effective_end_date' => ['nullable', 'date', 'after_or_equal:effective_start_date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}