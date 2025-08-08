<?php

namespace App\Http\Requests\Settings;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'username' => [
                'required',
                'string',
                'lowercase',
                'max:255',
                'min:3',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'birthplace' => ['required', 'string', 'max:255'],
            'birthdate' => ['required', 'date'],
            'gender' => ['required', 'string', 'in:l,p'],
            'phone' => ['required', 'numeric', 'digits_between:10,15'],
            'address' => ['nullable', 'string', 'max:255'],
        ];

        if ($this->hasFile('avatar')) {
            $rules['avatar'] = ['nullable', 'image', 'max:2048'];
        }

        return $rules;
    }
}
