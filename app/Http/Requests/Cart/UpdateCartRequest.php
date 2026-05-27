<?php

declare(strict_types=1);

namespace App\Http\Requests\Cart;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCartRequest extends FormRequest
{
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
            'key' => 'required|string',
            'quantity' => 'nullable|integer|min:0',
            'sku_id' => 'nullable|integer',
            'update_type' => 'nullable|string',
        ];
    }
}
