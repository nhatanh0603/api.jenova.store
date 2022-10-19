<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class AuthRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'user_name' => 'bail|required|string|max:40',
            'email' => 'bail|required|string|email|unique:users',
            'password'=> ['bail', 'required', 'string', Password::min(6)->letters()->numbers(), 'max:100']
        ];
    }
}
