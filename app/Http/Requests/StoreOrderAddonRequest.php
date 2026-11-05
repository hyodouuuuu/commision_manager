<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreOrderAddonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required_without:addons', 'string', 'max:255'],
            'price' => ['required_with:name', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'addons' => ['sometimes', 'required', 'array', 'min:1'],
            'addons.*.name' => ['required_with:addons', 'string', 'max:255'],
            'addons.*.price' => ['required_with:addons', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->exists('name') && $this->exists('addons')) {
                    $validator->errors()->add('addons', 'Provide either one add-on or an add-on list, not both.');
                }
            },
        ];
    }
}
