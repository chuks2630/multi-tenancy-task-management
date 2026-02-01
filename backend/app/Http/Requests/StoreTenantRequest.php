<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Organization details (flexible naming)
            'name' => 'sometimes|required|string|max:255',
            'organization_name' => 'sometimes|required|string|max:255',
            
            'subdomain' => [
                'sometimes',
                'required',
                'string',
                'min:3',
                'max:63',
                'regex:/^[a-z0-9-]+$/',
                'regex:/^[a-z0-9]/', // Must start with letter or number
                'regex:/[a-z0-9]$/', // Must end with letter or number
                Rule::unique('tenants', 'id'),
                Rule::notIn(['www', 'app', 'api', 'admin', 'mail', 'ftp', 'localhost', 'dashboard']),
            ],
            
            'plan_id' => 'sometimes|exists:plans,id',
            
            // Owner details
            'owner_name' => 'required|string|max:255',
            'owner_email' => 'required|email|max:255',
            'owner_password' => 'required|string|min:8',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Organization name is required',
            'organization_name.required' => 'Organization name is required',
            'subdomain.unique' => 'This subdomain is already taken',
            'subdomain.regex' => 'Subdomain can only contain lowercase letters, numbers, and hyphens',
            'subdomain.not_in' => 'This subdomain is reserved',
            'owner_email.unique' => 'An account with this email already exists',
        ];
    }

    /**
     * Prepare data for validation
     */
    protected function prepareForValidation()
    {
        // If subdomain not provided, generate from organization name
        if (!$this->has('subdomain')) {
            $orgName = $this->input('name') ?? $this->input('organization_name');
            if ($orgName) {
                $this->merge([
                    'subdomain' => \Str::slug($orgName),
                ]);
            }
        }
    }
}