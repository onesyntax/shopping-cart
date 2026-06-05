<?php

declare(strict_types=1);

namespace App\Cart\Infrastructure\Http;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the shape of an add-to-cart request. Domain rules about the
 * quantity (whole number, at least one) are enforced by the Quantity value
 * object in the use case; here we only require the fields to be present.
 */
final class AddItemToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'item_name' => ['required', 'string'],
            'quantity' => ['required'],
        ];
    }
}
