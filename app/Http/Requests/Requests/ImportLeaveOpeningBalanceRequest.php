<?php

namespace App\Http\Requests\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportLeaveOpeningBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('leave.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,leave_type_id'],
            'period_start_date' => ['required', 'date'],
            'period_end_date' => ['required', 'date', 'after_or_equal:period_start_date'],
            'transaction_date' => ['required', 'date'],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:5120'],
        ];
    }

    public function attributes(): array
    {
        return [
            'leave_type_id' => 'leave type',
            'period_start_date' => 'period start date',
            'period_end_date' => 'period end date',
            'transaction_date' => 'transaction date',
            'file' => 'import file',
        ];
    }
}