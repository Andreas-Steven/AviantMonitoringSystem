<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePayrollDeductionTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'deduction_code' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Z0-9_]+$/',
                Rule::unique('payroll_deduction_types', 'deduction_code'),
            ],
            'deduction_name' => [
                'required',
                'string',
                'max:255',
            ],
            'category_code' => [
                'required',
                'string',
                Rule::in([
                    'ATTENDANCE_FINE',
                    'ATTENDANCE_DEDUCTION',
                    'DAMAGE_CHARGE',
                    'LOSS_CHARGE',
                    'CASH_ADVANCE',
                    'MANUAL_ADJUSTMENT',
                ]),
            ],
            'debt_forming_default_flag' => [
                'nullable',
                'boolean',
            ],
            'active' => [
                'nullable',
                'boolean',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'deduction_code.required' => 'Deduction code wajib diisi.',
            'deduction_code.regex' => 'Deduction code hanya boleh huruf besar, angka, dan underscore.',
            'deduction_code.unique' => 'Deduction code sudah dipakai.',
            'deduction_name.required' => 'Deduction name wajib diisi.',
            'category_code.required' => 'Category wajib dipilih.',
            'category_code.in' => 'Category tidak valid.',
            'notes.max' => 'Notes maksimal 5000 karakter.',
        ];
    }
}