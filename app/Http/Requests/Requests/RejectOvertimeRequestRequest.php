<?php

namespace App\Http\Requests\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectOvertimeRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('overtime.approve') ?? false;
    }

    public function rules(): array
    {
        return [
            'approved_at' => ['nullable', 'date'],
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ];
    }
}