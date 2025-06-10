<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HRMUserStatusChangeRequest extends FormRequest
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
            'emailAddress' => 'required|email|max:255',
            'dateAt' => 'required|date',
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
            'emailAddress.required' => 'Email address is required.',
            'emailAddress.email' => 'Email address must be a valid email.',
            'dateAt.required' => 'Date is required.',
            'dateAt.date' => 'Date must be a valid date.',
        ];
    }
} 