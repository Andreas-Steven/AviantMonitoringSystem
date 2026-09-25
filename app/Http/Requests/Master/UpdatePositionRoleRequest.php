<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePositionRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('position_role.manage') ?? false;
    }

    public function rules(): array
    {
        $positionRoleId = (int) $this->route('position_role');

        return [
            'role_code' => ['required', 'string', 'max:50', Rule::unique('roles', 'role_code')->ignore($positionRoleId, 'role_id')],
            'role_name' => ['required', 'string', 'max:255'],
            'dept_id' => ['required', 'integer', 'exists:departments,dept_id'],
            'active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
