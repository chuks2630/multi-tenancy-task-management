<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Http\Responses\ApiResponse;
use App\Models\Tenant\Invitation;

class InviteUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'team_id' => 'nullable|exists:teams,id',
            'role' => 'sometimes|in:admin,member,viewer',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email is required',
            'email.email' => 'Please provide a valid email address',
            'team_id.exists' => 'The selected team does not exist',
            'role.in' => 'Role must be admin, member, or viewer',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Check if user is already invited
            $pendingInvitation = Invitation::where('email', $this->email)
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->first();

            if ($pendingInvitation) {
                $validator->errors()->add('email', 'This email already has a pending invitation');
            }
        });
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponse::validationError($validator->errors()->toArray())
        );
    }
}