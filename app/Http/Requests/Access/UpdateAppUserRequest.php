<?php

namespace App\Http\Requests\Access;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('user.manage') ?? false;
    }

    public function rules(): array
    {
        $userId = (int) $this->route('app_user');

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('app_users', 'email')->ignore($userId, 'user_id'),
            ],
            'employee_id' => [
                'nullable',
                'integer',
                'exists:employees,emp_id',
                Rule::unique('app_users', 'employee_id')->ignore($userId, 'user_id'),
            ],
            'password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],
            'is_active' => ['nullable', 'boolean'],
            'must_change_password' => ['nullable', 'boolean'],

            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => [
                'integer',
                'distinct',
                'exists:app_roles,app_role_id',
            ],

            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => [
                'integer',
                'distinct',
                'exists:branches,branch_id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.unique' => 'Employee tersebut sudah terhubung ke app user lain.',
        ];
    }
}