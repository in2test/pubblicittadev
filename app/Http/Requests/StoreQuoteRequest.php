<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreQuoteRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'exists:products,id'],
            'color_id' => ['nullable', 'exists:product_colors,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'customer_whatsapp' => ['nullable', 'string', 'max:50'],
            'customization_points' => ['nullable', 'array'],
            'customization_points.*' => ['string', 'max:100'],
            'design_file' => ['nullable', 'file', 'mimes:pdf,jpeg,png,jpg', 'max:10240'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
