<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Check if user is tenant owner
        return true; // Add proper authorization
    }

    public function rules(): array
    {
        return [
            'plan_id' => [
                'required',
                'exists:plans,id',
            ],
        ];
    }
}