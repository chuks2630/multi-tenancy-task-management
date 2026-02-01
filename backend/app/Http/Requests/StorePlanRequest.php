<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only admins can create plans
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9\-]+$/',
                Rule::unique('plans', 'slug'),
            ],
            'stripe_price_id' => [
                'nullable',
                'string',
                'starts_with:price_',
            ],
            'price' => [
                'required',
                'numeric',
                'min:0',
                'max:99999.99',
            ],
            'billing_period' => [
                'required',
                Rule::in(['monthly', 'yearly']),
            ],
            'features' => [
                'required',
                'array',
            ],
            'features.max_teams' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'features.max_users' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'features.max_boards' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'is_active' => [
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'Plan slug can only contain lowercase letters, numbers, and hyphens.',
            'stripe_price_id.starts_with' => 'Invalid Stripe price ID format.',
        ];
    }
}