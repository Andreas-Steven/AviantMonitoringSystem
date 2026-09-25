<?php

namespace App\Http\Requests\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GrantLeaveBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'transaction_date' => ['required', 'date'],
            'qty' => ['required', 'numeric', 'gt:0'],
            'source_ref_id' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'transaction_date.required' => 'Tanggal grant wajib diisi.',
            'transaction_date.date' => 'Tanggal grant tidak valid.',
            'qty.required' => 'Qty grant wajib diisi.',
            'qty.numeric' => 'Qty grant harus berupa angka.',
            'qty.gt' => 'Qty grant harus lebih besar dari 0.',
            'source_ref_id.required' => 'Source Ref ID wajib diisi agar grant tidak dobel.',
            'source_ref_id.max' => 'Source Ref ID maksimal 255 karakter.',
            'notes.max' => 'Notes maksimal 5000 karakter.',
        ];
    }
}