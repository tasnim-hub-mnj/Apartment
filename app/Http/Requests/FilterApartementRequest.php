<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FilterApartementRequest extends FormRequest
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
            'city' => 'nullable|string|max:100',
            'area' => 'nullable|string|max:100',
            'space' => 'nullable|numeric|min:10',
            'size' => 'nullable|in:small,medium,large',
            'price' => 'nullable|numeric|min:10',
        ];
    }
}
