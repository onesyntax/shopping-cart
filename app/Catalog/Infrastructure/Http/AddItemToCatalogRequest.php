<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Http;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the shape of an add-to-catalog request. Domain rules about the
 * fields (a positive price, a unique name) are enforced by the Item value
 * object and the AddItemToCatalog use case; here we only require the fields to
 * be present.
 */
final class AddItemToCatalogRequest extends FormRequest
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
            'name' => ['required', 'string'],
            'description' => ['required', 'string'],
            'price' => ['required', 'string'],
        ];
    }
}
