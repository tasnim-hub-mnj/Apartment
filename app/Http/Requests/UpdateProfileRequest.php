<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
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

            'first_name'=>'sometimes|string|max:100',
            'last_name'=>'sometimes|string|max:100',
            'personal_photo'=>'sometimes|image|mimes:png,jpg,jpeg,gif|max:4096',
            'birth_date'=>'sometimes|date|date_format:Y-m-d',
            'identity_photo'=>'sometimes|image|mimes:png,jpg,jpeg,gif|max:4096'
        ];
    }
}
