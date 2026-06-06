<?php

declare(strict_types=1);

namespace App\Checkout\Infrastructure\Http;

use App\Checkout\UseCases\ViewOrderConfirmation;
use App\Foundation\Infrastructure\Http\CurrentShopper;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The post-checkout confirmation page, showing the shopper's most recent order
 * and any invoice issued for it.
 */
final class OrderController
{
    public function confirmation(Request $request, ViewOrderConfirmation $viewConfirmation): View|RedirectResponse
    {
        $confirmation = $viewConfirmation->handle(CurrentShopper::id($request));

        if ($confirmation === null) {
            return redirect('/')->with('error', 'You have no recent orders.');
        }

        return view('orders.confirmation', ['confirmation' => $confirmation]);
    }
}
