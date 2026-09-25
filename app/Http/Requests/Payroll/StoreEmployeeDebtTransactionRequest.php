<?php

namespace App\Http\Requests\Payroll;

use App\Domains\Payroll\Models\EmployeeDebt;
use App\Domains\Payroll\Models\PayrollDeduction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeDebtTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'transaction_type_code' => [
                'required',
                'string',
                'exists:employee_debt_transaction_types,transaction_type_code',
            ],
            'transaction_date' => [
                'required',
                'date',
            ],
            'amount' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'payment_source_code' => [
                'required',
                'string',
                Rule::in(['PAYROLL', 'NON_PAYROLL', 'MANUAL']),
            ],
            'payroll_period_id' => [
                'nullable',
                'integer',
                'exists:payroll_periods,payroll_period_id',
            ],
            'payroll_deduction_id' => [
                'nullable',
                'integer',
                'exists:payroll_deductions,payroll_deduction_id',
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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $debtId = (int) $this->route('debt');
            $debt = EmployeeDebt::query()->find($debtId);

            if (!$debt) {
                return;
            }

            $amount = (float) $this->input('amount');
            $type = (string) $this->input('transaction_type_code');
            $paymentSource = (string) $this->input('payment_source_code');
            $payrollDeductionId = $this->input('payroll_deduction_id');

            if ((string) $debt->status_code !== 'OPEN') {
                $validator->errors()->add('transaction_type_code', 'Hanya debt OPEN yang boleh ditambah transaksi.');
            }

            $reducingTypes = [
                'PAYROLL_INSTALLMENT',
                'NON_PAYROLL_PAYMENT',
                'FULL_SETTLEMENT',
                'ADJUST_MINUS',
            ];

            if (in_array($type, $reducingTypes, true) && $amount > (float) $debt->outstanding_amount) {
                $validator->errors()->add('amount', 'Nominal transaksi tidak boleh melebihi outstanding debt.');
            }

            if ($paymentSource === 'PAYROLL' && !$this->filled('payroll_period_id')) {
                $validator->errors()->add('payroll_period_id', 'Payroll period wajib dipilih untuk payment source PAYROLL.');
            }

            if ($payrollDeductionId) {
                $deduction = PayrollDeduction::query()->find($payrollDeductionId);

                if ($deduction) {
                    if ((int) $deduction->employee_debt_id !== (int) $debt->employee_debt_id) {
                        $validator->errors()->add(
                            'payroll_deduction_id',
                            'Payroll deduction yang dipilih tidak terkait ke debt ini.'
                        );
                    }

                    $alreadyUsed = \App\Domains\Payroll\Models\EmployeeDebtTransaction::query()
                        ->where('payroll_deduction_id', (int) $payrollDeductionId)
                        ->exists();

                    if ($alreadyUsed) {
                        $validator->errors()->add(
                            'payroll_deduction_id',
                            'Payroll deduction tersebut sudah pernah dipakai pada debt transaction.'
                        );
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'transaction_type_code.required' => 'Transaction type wajib dipilih.',
            'transaction_type_code.exists' => 'Transaction type tidak ditemukan.',
            'transaction_date.required' => 'Transaction date wajib diisi.',
            'amount.required' => 'Amount wajib diisi.',
            'amount.numeric' => 'Amount harus berupa angka.',
            'amount.gt' => 'Amount harus lebih besar dari 0.',
            'payment_source_code.required' => 'Payment source wajib dipilih.',
            'payment_source_code.in' => 'Payment source tidak valid.',
            'payroll_period_id.exists' => 'Payroll period tidak ditemukan.',
            'payroll_deduction_id.exists' => 'Payroll deduction tidak ditemukan.',
            'notes.max' => 'Notes maksimal 5000 karakter.',
        ];
    }
}