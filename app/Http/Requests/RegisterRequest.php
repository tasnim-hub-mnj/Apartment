<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
        'phone'=>'required|string|max:10|min:10|unique:users,phone',
        'role'=>'required|in:owner,renter,admin',
        'password'=>'required|string|min:6',
        'first_name'=>'required|string|max:30',
        'last_name'=>'required|string|max:30',
        'birth_date'=>'required|max:30|date|date_format:Y-m-d',
        'token_fcm'=>'required|string|max:400',
        'personal_photo'=>'required|image|mimes:png,jpg,jpeg,gif|max:4096',
        'identity_photo'=>'required|image|mimes:png,jpg,jpeg,gif|max:4096',
        ];
    }
}
