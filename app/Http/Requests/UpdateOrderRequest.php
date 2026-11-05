<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateOrderRequest extends FormRequest
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
            'customer_id' => ['sometimes', 'nullable', 'integer', 'exists:customers,id'],
            'customer' => ['sometimes', 'array'],
            'customer.account_name' => ['required_with:customer', 'string', 'max:255'],
            'customer.username' => ['required_with:customer', 'string', 'max:255'],
            'customer.platform' => ['required_with:customer', 'string', 'max:255'],
            'customer.notes' => ['nullable', 'string'],
            'package_id' => ['sometimes', 'nullable', 'integer', 'exists:packages,id'],
            'package_name' => ['sometimes', 'required', 'string', 'max:255'],
            'package_price' => ['sometimes', 'required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'order_date' => ['sometimes', 'required', 'date'],
            'agreed_start_date' => ['sometimes', 'nullable', 'date'],
            'actual_start_date' => ['sometimes', 'nullable', 'date'],
            'deadline' => ['sometimes', 'nullable', 'date'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'work_status' => ['sometimes', 'in:belum_dikerjakan,on_progress,selesai'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->exists('customer_id') && $this->exists('customer')) {
                    $validator->errors()->add('customer', 'Provide either a customer ID or new customer details, not both.');
                }

                if ($this->exists('package_id') && $this->input('package_id') === null && ! $this->exists('package_name')) {
                    $validator->errors()->add('package_name', 'A package name is required when removing the selected package.');
                }
            },
        ];
    }
}
