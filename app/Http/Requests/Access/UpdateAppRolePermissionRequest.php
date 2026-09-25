<?php

namespace App\Http\Requests\Access;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppRolePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('role.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => [
                'integer',
                'distinct',
                'exists:app_permissions,permission_id',
            ],
        ];
    }
}