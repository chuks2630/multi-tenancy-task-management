<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Http\Responses\ApiResponse;

class UpdateTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Check if user can update team (owner, admin, or team leader)
        $team = $this->route('team');
        $user = $this->user();

        return $user->isOwner() 
            || $user->isAdmin() 
            || $team->hasLeader($user)
            || $team->created_by === $user->id;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'sometimes|boolean',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponse::validationError($validator->errors()->toArray())
        );
    }

    protected function failedAuthorization()
    {
        throw new HttpResponseException(
            ApiResponse::forbidden('You do not have permission to update this team')
        );
    }
}