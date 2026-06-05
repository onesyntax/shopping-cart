<?php

declare(strict_types=1);

namespace App\Cart\Infrastructure\Http;

use Illuminate\Foundation\Http\FormRequest;

final class RemoveItemFromCartRequest extends FormRequest
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
        ];
    }
}
