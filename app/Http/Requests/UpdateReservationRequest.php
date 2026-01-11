<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReservationRequest extends FormRequest
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

            'status'=>'sometimes|in:confirmed,cancelled,finished',
            'approv_status_reserv'=>'sometimes|in:pending,approved,rejected',
            'start_date'=>'required|date|date_format:Y-m-d',
            'end_date' => 'required|date|after_or_equal:start_date|date_format:Y-m-d',
            'pay_method'=>'required|in:card,cash',
            'card_number' => 'nullable|numeric|required_if:pay_method,card',
            // 'status_pay'=>'sometimes|in:unpaid,paid',
            'required_amount'=>'sometimes|numeric|min:0',


        ];
    }
}
