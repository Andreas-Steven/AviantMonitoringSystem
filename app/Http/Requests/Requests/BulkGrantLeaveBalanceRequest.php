<?php

namespace App\Http\Requests\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkGrantLeaveBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,leave_type_id'],
            'transaction_date' => ['required', 'date'],
            'qty' => ['required', 'numeric', 'gt:0'],
            'employment_type_ids' => ['required', 'array', 'min:1'],
            'employment_type_ids.*' => ['integer', 'exists:employment_types,employment_type_id'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'leave_type_id.required' => 'Leave type wajib dipilih.',
            'transaction_date.required' => 'Tanggal grant wajib diisi.',
            'qty.required' => 'Jumlah grant wajib diisi.',
            'qty.gt' => 'Jumlah grant harus lebih besar dari 0.',
            'employment_type_ids.required' => 'Pilih minimal satu employment type.',
            'employment_type_ids.min' => 'Pilih minimal satu employment type.',
            'notes.max' => 'Notes maksimal 5000 karakter.',
        ];
    }
}