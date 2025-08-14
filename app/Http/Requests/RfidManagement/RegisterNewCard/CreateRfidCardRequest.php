<?php

namespace App\Http\Requests\RfidManagement\RegisterNewCard;

use Illuminate\Foundation\Http\FormRequest;

class CreateRfidCardRequest extends FormRequest
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
            'uid' => ['required', 'string', 'max:32', 'unique:sys_user_rfids,uid'],
            'user_id' => ['required', 'exists:sys_users,id', 'unique:sys_user_rfids,user_id'],
        ];
    }

     public function attributes(): array
    {
        return [
            'uid' => 'RFID',
            'user_id' => 'user'
        ];
    }
}
