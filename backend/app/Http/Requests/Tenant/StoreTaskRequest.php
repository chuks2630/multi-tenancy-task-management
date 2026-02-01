<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Http\Responses\ApiResponse;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'board_id' => 'required|exists:boards,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'status' => 'sometimes|in:todo,in_progress,done',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'assigned_to' => 'nullable|exists:users,id',
            'due_date' => 'nullable|date|after:now',
        ];
    }

    public function messages(): array
    {
        return [
            'board_id.required' => 'Board is required',
            'board_id.exists' => 'The selected board does not exist',
            'title.required' => 'Task title is required',
            'title.max' => 'Task title cannot exceed 255 characters',
            'status.in' => 'Status must be: todo, in_progress, or done',
            'priority.in' => 'Priority must be: low, medium, high, or urgent',
            'assigned_to.exists' => 'The selected user does not exist',
            'due_date.after' => 'Due date must be in the future',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponse::validationError($validator->errors()->toArray())
        );
    }
}