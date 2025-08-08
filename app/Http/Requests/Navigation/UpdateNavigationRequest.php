<?php

namespace App\Http\Requests\Navigation;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNavigationRequest extends FormRequest
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
            'controller_name' => [
                'nullable',
                'max:255',
                Rule::unique('sys_menus', 'controller_name')->ignore($this->sysMenu?->id),
            ],
            'route_name' => 'required_if:is_divider,false|nullable|max:255',
            'url' => 'required_if:is_divider,false|nullable|max:255',
            'is_active' => 'boolean',
            'is_divider' => 'boolean',
        ];
    }
}
