<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Add authorization logic (e.g., only owner can update)
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
            ],
            'settings' => [
                'sometimes',
                'array',
            ],
            'settings.*.key' => [
                'required_with:settings',
                'string',
            ],
            'settings.*.value' => [
                'required_with:settings',
            ],
        ];
    }
}