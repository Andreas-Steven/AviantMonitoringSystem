<?php

namespace App\Http\Requests\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('leave.approve') ?? false;
    }

    public function rules(): array
    {
        return [
            'approved_at' => ['nullable', 'date'],
            'approval_note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}