<?php

namespace App\Http\Requests\Review;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateAttendanceExceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('attendance_exception.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'emp_id' => ['required', 'integer', 'exists:employees,emp_id'],
            'work_date' => ['required', 'date'],
            'exception_type_code' => ['required', 'string', 'exists:attendance_exception_types,exception_type_code'],

            'minutes_value' => ['nullable', 'integer'],
            'time_value' => ['nullable', 'date'],
            'shift_id_value' => ['nullable', 'integer', 'exists:shifts,shift_id'],
            'status_value_code' => ['nullable', 'string', 'exists:attendance_statuses,attendance_status_code'],

            'reason' => ['nullable', 'string'],
            'approved_by' => ['nullable', 'integer', 'exists:employees,emp_id'],
            'approved_at' => ['nullable', 'date'],
            'source_type_code' => ['nullable', 'string', 'exists:source_types,source_type_code'],
            'source_ref_id' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = (string) $this->input('exception_type_code');

            if (in_array($type, ['LATE_DISPENSATION', 'EARLY_OUT_DISPENSATION', 'OVERTIME_OVERRIDE'], true)
                && !$this->filled('minutes_value')) {
                $validator->errors()->add(
                    'minutes_value',
                    'Minutes value wajib diisi untuk exception type ini.'
                );
            }

            if (in_array($type, ['MANUAL_IN', 'MANUAL_OUT'], true)
                && !$this->filled('time_value')) {
                $validator->errors()->add(
                    'time_value',
                    'Time value wajib diisi untuk exception type ini.'
                );
            }

            if ($type === 'SHIFT_OVERRIDE' && !$this->filled('shift_id_value')) {
                $validator->errors()->add(
                    'shift_id_value',
                    'Shift override wajib diisi untuk SHIFT_OVERRIDE.'
                );
            }

            if (in_array($type, ['FORCE_PRESENT', 'FORCE_ABSENT'], true)
                && !$this->filled('status_value_code')) {
                $validator->errors()->add(
                    'status_value_code',
                    'Status override wajib diisi untuk exception type ini.'
                );
            }

            if (in_array($type, [
                'LATE_DISPENSATION',
                'EARLY_OUT_DISPENSATION',
                'FORCE_PRESENT',
                'FORCE_ABSENT',
                'SHIFT_OVERRIDE',
                'FORGOT_CHECKIN_APPROVAL',
                'FORGOT_CHECKOUT_APPROVAL',
                'MANUAL_IN',
                'MANUAL_OUT',
                'OVERTIME_OVERRIDE',
            ], true) && !$this->filled('reason')) {
                $validator->errors()->add(
                    'reason',
                    'Reason wajib diisi untuk attendance exception.'
                );
            }

            if ($this->filled('approved_at') && !$this->filled('approved_by')) {
                $validator->errors()->add(
                    'approved_by',
                    'Approved by wajib diisi jika approved at diisi.'
                );
            }

            if ($this->filled('approved_by') && !$this->filled('approved_at')) {
                $validator->errors()->add(
                    'approved_at',
                    'Approved at wajib diisi jika approved by diisi.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'emp_id.required' => 'Employee wajib dipilih.',
            'emp_id.exists' => 'Employee tidak valid.',

            'work_date.required' => 'Work date wajib diisi.',
            'work_date.date' => 'Format work date tidak valid.',

            'exception_type_code.required' => 'Exception type wajib dipilih.',
            'exception_type_code.exists' => 'Exception type tidak valid.',

            'minutes_value.integer' => 'Minutes value harus berupa angka bulat.',
            'time_value.date' => 'Format time value tidak valid.',
            'shift_id_value.exists' => 'Shift override tidak valid.',
            'status_value_code.exists' => 'Status override tidak valid.',

            'approved_by.exists' => 'Approved by tidak valid.',
            'approved_at.date' => 'Format approved at tidak valid.',
            'source_type_code.exists' => 'Source type tidak valid.',
            'source_ref_id.max' => 'Source ref ID maksimal 255 karakter.',
        ];
    }
}