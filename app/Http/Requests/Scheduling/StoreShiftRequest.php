<?php

namespace App\Http\Requests\Scheduling;

use Illuminate\Foundation\Http\FormRequest;

class StoreShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('shift.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'break_min' => (int) $this->input('break_min', 0),
            'cross_day_flag' => $this->boolean('cross_day_flag'),
            'active' => $this->boolean('active'),
            'default_work_min' => $this->calculateDefaultWorkMinutes(),
        ]);
    }

    public function rules(): array
    {
        return [
            'shift_code' => ['required', 'string', 'max:255', 'unique:shifts,shift_code'],
            'shift_name' => ['required', 'string', 'max:255'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'break_min' => ['required', 'integer', 'min:0'],
            'default_work_min' => ['required', 'integer', 'min:0'],
            'cross_day_flag' => ['required', 'boolean'],
            'active' => ['required', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function calculateDefaultWorkMinutes(): int
    {
        $start = $this->input('start_time');
        $end = $this->input('end_time');

        if (!$start || !$end) {
            return 0;
        }

        [$startHour, $startMinute] = array_map('intval', explode(':', $start));
        [$endHour, $endMinute] = array_map('intval', explode(':', $end));

        $startMinutes = ($startHour * 60) + $startMinute;
        $endMinutes = ($endHour * 60) + $endMinute;

        if ($this->boolean('cross_day_flag') && $endMinutes <= $startMinutes) {
            $endMinutes += 24 * 60;
        }

        $durationMinutes = max(0, $endMinutes - $startMinutes);
        $breakMinutes = max(0, (int) $this->input('break_min', 0));

        return max(0, $durationMinutes - $breakMinutes);
    }
}