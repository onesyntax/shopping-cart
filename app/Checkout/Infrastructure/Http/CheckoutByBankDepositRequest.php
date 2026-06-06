<?php

declare(strict_types=1);

namespace App\Checkout\Infrastructure\Http;

use Illuminate\Foundation\Http\FormRequest;

final class CheckoutByBankDepositRequest extends FormRequest
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
            'deposit_reference' => ['required', 'string'],
            'deposit_date' => ['required', 'date'],
        ];
    }
}
