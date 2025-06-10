<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HRMUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'sex' => 'required|integer|in:0,1,2', // 0: Unknown, 1: Male, 2: Female
            'type' => 'required|integer|in:0,1,2,3,4,5,6,7,8,9', // UserType enum values
            'emailAddress' => 'required|email|max:255',
            'fullName' => 'required|string|max:255',
            'branchCode' => 'nullable|string|max:50',
            'levelCode' => 'nullable|string|max:50',
            'positionCode' => 'nullable|string|max:50',
            'workingStartDate' => 'required|date',
            'skillNames' => 'nullable|array',
            'skillNames.*' => 'string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'sex.required' => 'Sex is required.',
            'sex.integer' => 'Sex must be an integer.',
            'sex.in' => 'Sex must be 0 (Unknown), 1 (Male), or 2 (Female).',
            'type.required' => 'User type is required.',
            'type.integer' => 'User type must be an integer.',
            'type.in' => 'Invalid user type value.',
            'emailAddress.required' => 'Email address is required.',
            'emailAddress.email' => 'Email address must be a valid email.',
            'fullName.required' => 'Full name is required.',
            'workingStartDate.required' => 'Working start date is required.',
            'workingStartDate.date' => 'Working start date must be a valid date.',
            'skillNames.array' => 'Skill names must be an array.',
            'skillNames.*.string' => 'Each skill name must be a string.',
        ];
    }
} 