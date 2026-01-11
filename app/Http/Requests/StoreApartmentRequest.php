<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreApartmentRequest extends FormRequest
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
            'city'=>'required|string|max:100',
            'area'=>'required|string|max:100',
            'space'=>'required|string|max:10',
            'address'=>'required|string|max:100',
            'room'=>'string|max:10',
            'bath_room'=>'string|max:10',
            'price'=>'required|string|max:10',
            'image'=>'required|image|mimes:png,jpg,jpeg,gif|max:4096',
            'is_available'=>'boolean'
        ];
    }
}
