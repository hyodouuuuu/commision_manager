<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
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
            'customer_id' => ['required_without:customer', 'nullable', 'integer', 'exists:customers,id'],
            'customer' => ['required_without:customer_id', 'array'],
            'customer.account_name' => ['required_with:customer', 'string', 'max:255'],
            'customer.username' => ['required_with:customer', 'string', 'max:255'],
            'customer.platform' => ['required_with:customer', 'string', 'max:255'],
            'customer.notes' => ['nullable', 'string'],
            'package_id' => ['nullable', 'integer', 'exists:packages,id'],
            'package_name' => ['required_without:package_id', 'nullable', 'string', 'max:255'],
            'package_price' => ['sometimes', 'required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'order_date' => ['required', 'date'],
            'agreed_start_date' => ['nullable', 'date'],
            'actual_start_date' => ['nullable', 'date'],
            'deadline' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'work_status' => ['sometimes', 'in:belum_dikerjakan,on_progress,selesai'],
        ];
    }
}
