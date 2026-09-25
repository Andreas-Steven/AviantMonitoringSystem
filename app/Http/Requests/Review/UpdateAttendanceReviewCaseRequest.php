<?php

namespace App\Http\Requests\Review;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAttendanceReviewCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('attendance_review.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'review_status_code' => [
                'required',
                'string',
                Rule::in([
                    'OPEN',
                    'IN_REVIEW',
                    'RESOLVED',
                    'REJECTED',
                    'CLOSED',
                ]),
            ],

            'resolution_type_code' => [
                'nullable',
                'string',
                Rule::in([
                    'NO_ACTION',
                    'MANUAL_CORRECTION',
                    'APPROVED_OVERRIDE',
                    'REJECTED_CASE',
                    'SYSTEM_ADJUSTMENT',
                ]),
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
            'review_status_code.required' => 'Review status wajib dipilih.',
            'review_status_code.in' => 'Review status tidak valid.',

            'resolution_type_code.in' => 'Resolution type tidak valid.',

            'notes.max' => 'Notes maksimal 5000 karakter.',
        ];
    }
}