<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeDebtRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'emp_id' => [
                'required',
                'integer',
                'exists:employees,emp_id',
            ],
            'use_custom_debt_code' => [
                'nullable',
                'boolean',
            ],
            'debt_code' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[A-Z0-9\-_]+$/',
                Rule::unique('employee_debts', 'debt_code'),
            ],
            'debt_name' => [
                'required',
                'string',
                'max:255',
            ],
            'debt_category_code' => [
                'required',
                'string',
                Rule::in([
                    'DAMAGE_CHARGE',
                    'LOSS_CHARGE',
                    'CASH_ADVANCE',
                    'MANUAL_DEBT',
                ]),
            ],
            'origin_date' => [
                'required',
                'date',
            ],
            'original_amount' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'source_type_code' => [
                'nullable',
                'string',
                'exists:source_types,source_type_code',
            ],
            'source_ref_id' => [
                'nullable',
                'string',
                'max:255',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'debt_code' => $this->filled('debt_code')
                ? strtoupper(trim((string) $this->input('debt_code')))
                : null,
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->boolean('use_custom_debt_code') && !$this->filled('debt_code')) {
                $validator->errors()->add(
                    'debt_code',
                    'Debt code wajib diisi jika menggunakan custom code.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'emp_id.required' => 'Employee wajib dipilih.',
            'emp_id.exists' => 'Employee tidak ditemukan.',
            'debt_code.regex' => 'Debt code hanya boleh huruf besar, angka, dash, dan underscore.',
            'debt_code.unique' => 'Debt code sudah dipakai. Gunakan kode debt yang berbeda.',
            'debt_name.required' => 'Debt name wajib diisi.',
            'debt_category_code.required' => 'Debt category wajib dipilih.',
            'debt_category_code.in' => 'Debt category tidak valid.',
            'origin_date.required' => 'Origin date wajib diisi.',
            'original_amount.required' => 'Original amount wajib diisi.',
            'original_amount.numeric' => 'Original amount harus berupa angka.',
            'original_amount.gt' => 'Original amount harus lebih besar dari 0.',
            'notes.max' => 'Notes maksimal 5000 karakter.',
        ];
    }
}