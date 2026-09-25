<?php

namespace App\Http\Requests\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('leave.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'emp_id' => ['required', 'integer', 'exists:employees,emp_id'],
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,leave_type_id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'partial_day_flag' => ['nullable', 'boolean'],
            'partial_start_time' => ['nullable', 'required_if:partial_day_flag,1', 'date_format:H:i'],
            'partial_end_time' => ['nullable', 'required_if:partial_day_flag,1', 'date_format:H:i', 'after:partial_start_time'],
            'reason' => ['nullable', 'string'],
            'attachment_url' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'partial_start_time.required_if' => 'Partial start time wajib diisi untuk partial day request.',
            'partial_end_time.required_if' => 'Partial end time wajib diisi untuk partial day request.',
            'partial_end_time.after' => 'Partial end time harus lebih besar dari partial start time.',
        ];
    }
}