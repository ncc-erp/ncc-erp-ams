<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HRMUserHookRequest extends FormRequest
{   
    public function rules()
    {
        return [
            'emailAddress' => 'required|email',
        ];
    }
}
