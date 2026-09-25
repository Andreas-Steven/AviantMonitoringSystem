<?php

namespace App\Http\Requests\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('leave.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'cancel_reason' => ['required', 'string', 'max:1000'],
        ];
    }
}