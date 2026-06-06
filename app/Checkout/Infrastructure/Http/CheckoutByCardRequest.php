<?php

declare(strict_types=1);

namespace App\Checkout\Infrastructure\Http;

use Illuminate\Foundation\Http\FormRequest;

final class CheckoutByCardRequest extends FormRequest
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
            'card_token' => ['required', 'string'],
        ];
    }
}
