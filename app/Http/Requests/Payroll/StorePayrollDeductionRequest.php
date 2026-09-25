<?php

namespace App\Http\Requests\Payroll;

use App\Domains\Payroll\Models\EmployeeDebt;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePayrollDeductionRequest extends FormRequest
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
            'payroll_period_id' => [
                'required',
                'integer',
                'exists:payroll_periods,payroll_period_id',
            ],
            'payroll_deduction_type_id' => [
                'required',
                'integer',
                'exists:payroll_deduction_types,payroll_deduction_type_id',
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
            'description' => [
                'nullable',
                'string',
                'max:255',
            ],
            'qty' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'rate_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'debt_forming_flag' => [
                'nullable',
                'boolean',
            ],
            'employee_debt_id' => [
                'nullable',
                'integer',
                'exists:employee_debts,employee_debt_id',
            ],

            'create_new_debt' => [
                'nullable',
                'boolean',
            ],
            'use_custom_debt_code' => [
                'nullable',
                'boolean',
            ],
            'new_debt_code' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[A-Z0-9\-_]+$/',
                Rule::unique('employee_debts', 'debt_code'),
            ],
            'new_debt_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'new_debt_category_code' => [
                'nullable',
                'string',
                Rule::in([
                    'DAMAGE_CHARGE',
                    'LOSS_CHARGE',
                    'CASH_ADVANCE',
                    'MANUAL_DEBT',
                ]),
            ],
            'new_debt_origin_date' => [
                'nullable',
                'date',
            ],
            'new_debt_notes' => [
                'nullable',
                'string',
                'max:5000',
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
            'new_debt_code' => $this->filled('new_debt_code')
                ? strtoupper(trim((string) $this->input('new_debt_code')))
                : null,
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $isDebtForming = $this->boolean('debt_forming_flag');
            $createNewDebt = $this->boolean('create_new_debt');
            $useCustomCode = $this->boolean('use_custom_debt_code');
            $existingDebtId = $this->input('employee_debt_id');

            $qty = (float) ($this->input('qty') ?? 0);
            $rateAmount = (float) ($this->input('rate_amount') ?? 0);
            $inputAmount = $this->filled('amount') ? (float) $this->input('amount') : null;
            $finalAmount = $inputAmount !== null ? $inputAmount : ($qty * $rateAmount);

            if ($finalAmount <= 0) {
                $validator->errors()->add('amount', 'Final amount harus lebih besar dari 0.');
            }

            if (!$isDebtForming) {
                if ($createNewDebt || $existingDebtId || $useCustomCode) {
                    $validator->errors()->add(
                        'debt_forming_flag',
                        'Debt integration hanya boleh dipakai jika debt-forming dicentang.'
                    );
                }

                return;
            }

            if ($createNewDebt && $existingDebtId) {
                $validator->errors()->add(
                    'employee_debt_id',
                    'Pilih salah satu: link debt existing atau create new debt.'
                );
            }

            if (!$createNewDebt && !$existingDebtId) {
                $validator->errors()->add(
                    'employee_debt_id',
                    'Untuk debt-forming deduction, pilih hutang existing atau create hutang baru.'
                );
            }

            if ($createNewDebt) {
                if ($useCustomCode && !$this->filled('new_debt_code')) {
                    $validator->errors()->add(
                        'new_debt_code',
                        'Debt code wajib diisi jika menggunakan custom code.'
                    );
                }

                if (!$this->filled('new_debt_name')) {
                    $validator->errors()->add(
                        'new_debt_name',
                        'Debt name wajib diisi saat create new debt.'
                    );
                }

                if (!$this->filled('new_debt_category_code')) {
                    $validator->errors()->add(
                        'new_debt_category_code',
                        'Debt category wajib dipilih saat create new debt.'
                    );
                }

                if (!$this->filled('new_debt_origin_date')) {
                    $validator->errors()->add(
                        'new_debt_origin_date',
                        'Origin date wajib diisi saat create new debt.'
                    );
                }
            }

            if ($existingDebtId) {
                $debt = EmployeeDebt::query()->find($existingDebtId);

                if ($debt) {
                    if ((int) $debt->emp_id !== (int) $this->input('emp_id')) {
                        $validator->errors()->add(
                            'employee_debt_id',
                            'Debt yang dipilih tidak milik employee yang sama.'
                        );
                    }

                    if ((string) $debt->status_code !== 'OPEN') {
                        $validator->errors()->add(
                            'employee_debt_id',
                            'Hanya debt dengan status OPEN yang boleh dipakai.'
                        );
                    }

                    if ((float) $debt->outstanding_amount <= 0) {
                        $validator->errors()->add(
                            'employee_debt_id',
                            'Debt yang dipilih sudah tidak memiliki outstanding.'
                        );
                    }

                    if ($finalAmount > (float) $debt->outstanding_amount) {
                        $validator->errors()->add(
                            'amount',
                            'Installment payroll tidak boleh melebihi outstanding debt.'
                        );
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'emp_id.required' => 'Employee wajib dipilih.',
            'emp_id.exists' => 'Employee tidak ditemukan.',
            'payroll_period_id.required' => 'Payroll period wajib dipilih.',
            'payroll_period_id.exists' => 'Payroll period tidak ditemukan.',
            'payroll_deduction_type_id.required' => 'Deduction type wajib dipilih.',
            'payroll_deduction_type_id.exists' => 'Deduction type tidak ditemukan.',
            'qty.required' => 'Qty wajib diisi.',
            'qty.numeric' => 'Qty harus berupa angka.',
            'qty.gt' => 'Qty harus lebih besar dari 0.',
            'rate_amount.numeric' => 'Rate harus berupa angka.',
            'rate_amount.min' => 'Rate tidak boleh negatif.',
            'amount.numeric' => 'Amount harus berupa angka.',
            'amount.min' => 'Amount tidak boleh negatif.',
            'employee_debt_id.exists' => 'Debt yang dipilih tidak ditemukan.',
            'new_debt_code.regex' => 'Debt code hanya boleh huruf besar, angka, dash, dan underscore.',
            'new_debt_code.unique' => 'Debt code sudah dipakai. Gunakan kode debt yang berbeda.',
            'notes.max' => 'Notes maksimal 5000 karakter.',
            'new_debt_notes.max' => 'Debt notes maksimal 5000 karakter.',
        ];
    }
}