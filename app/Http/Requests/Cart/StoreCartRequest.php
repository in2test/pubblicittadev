<?php

declare(strict_types=1);

namespace App\Http\Requests\Cart;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCartRequest extends FormRequest
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
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'color_id' => 'nullable|integer',
            'color_name' => 'nullable|string',
            'size_id' => 'nullable|integer',
            'size_name' => 'nullable|string',
            'print_placements' => 'nullable|string',
            'product_name' => 'required|string',
            'product_slug' => 'required|string',
            'image_url' => 'nullable|string',
        ];
    }
}
