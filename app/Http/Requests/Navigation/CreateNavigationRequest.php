<?php

namespace App\Http\Requests\Navigation;

use Illuminate\Foundation\Http\FormRequest;

class CreateNavigationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'icon' => 'required_if:is_divider,false|nullable|max:255',
            'label_name' => 'required|max:255',
            'controller_name' => 'nullable|max:255|unique:sys_menus,controller_name',
            'route_name' => 'required_if:is_divider,false|nullable|max:255',
            'url' => 'required_if:is_divider,false|nullable|max:255',
            'is_active' => 'boolean',
            'is_divider' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            '*.required_if' => 'The :attribute field is required when :other is disabled.',
        ];
    }

    public function attributes(): array
    {
        return [
            'controller_name' => 'controller name',
            'route_name' => 'route name',
            'label_name' => 'label name',
            'url' => 'url',
            'icon' => 'icon',
            'is_active' => 'active menu',
            'is_divider' => 'divider menu',
        ];
    }

}
