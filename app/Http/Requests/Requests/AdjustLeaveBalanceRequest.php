<?php

namespace App\Http\Requests\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustLeaveBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'adjustment_mode' => [
                'required',
                'string',
                Rule::in(['PLUS', 'MINUS']),
            ],

            'transaction_date' => [
                'required',
                'date',
            ],

            'qty' => [
                'required',
                'numeric',
                'gt:0',
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

    public function messages(): array
    {
        return [
            'adjustment_mode.required' => 'Adjustment mode wajib dipilih.',
            'adjustment_mode.in' => 'Adjustment mode tidak valid.',
            'transaction_date.required' => 'Tanggal transaksi wajib diisi.',
            'transaction_date.date' => 'Tanggal transaksi tidak valid.',
            'qty.required' => 'Qty wajib diisi.',
            'qty.numeric' => 'Qty harus berupa angka.',
            'qty.gt' => 'Qty harus lebih besar dari 0.',
            'source_ref_id.max' => 'Source ref ID maksimal 255 karakter.',
            'notes.max' => 'Notes maksimal 5000 karakter.',
        ];
    }
}