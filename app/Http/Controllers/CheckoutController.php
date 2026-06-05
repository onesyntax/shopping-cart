<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Cart\ViewCart;
use App\Application\Checkout\CheckoutByBankDeposit;
use App\Application\Checkout\CheckoutByBankDepositInput;
use App\Application\Checkout\CheckoutByCard;
use App\Application\Checkout\CheckoutByCardInput;
use App\Application\Checkout\CheckoutByCashOnDelivery;
use App\Application\Checkout\CheckoutByCashOnHand;
use App\Application\Checkout\CheckoutInput;
use App\Domain\Shared\DomainException;
use App\Http\Requests\CheckoutByBankDepositRequest;
use App\Http\Requests\CheckoutByCardRequest;
use App\Http\Support\CurrentShopper;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Checkout. Shows the payment options for a non-empty cart, then runs one
 * use case per payment method. A successful checkout lands on the order
 * confirmation; a domain-rule violation (empty cart, declined card, unreachable
 * gateway) sends the shopper back with the reason.
 */
final class CheckoutController
{
    public function show(Request $request, ViewCart $viewCart): View|RedirectResponse
    {
        $cart = $viewCart->handle(CurrentShopper::id($request));

        if ($cart->isEmpty()) {
            return redirect('/cart')->with('error', 'Your cart is empty.');
        }

        return view('checkout.show', ['cart' => $cart]);
    }

    public function card(CheckoutByCardRequest $request, CheckoutByCard $useCase): RedirectResponse
    {
        return $this->place($request, fn (string $owner) => $useCase->handle(
            new CheckoutByCardInput($owner, $request->string('card_token')->toString()),
        ));
    }

    public function bankDeposit(CheckoutByBankDepositRequest $request, CheckoutByBankDeposit $useCase): RedirectResponse
    {
        return $this->place($request, fn (string $owner) => $useCase->handle(
            new CheckoutByBankDepositInput(
                $owner,
                $request->string('deposit_reference')->toString(),
                $request->string('deposit_date')->toString(),
            ),
        ));
    }

    public function cashOnDelivery(Request $request, CheckoutByCashOnDelivery $useCase): RedirectResponse
    {
        return $this->place($request, fn (string $owner) => $useCase->handle(new CheckoutInput($owner)));
    }

    public function cashOnHand(Request $request, CheckoutByCashOnHand $useCase): RedirectResponse
    {
        return $this->place($request, fn (string $owner) => $useCase->handle(new CheckoutInput($owner)));
    }

    /**
     * Run a checkout use case for the current shopper and route the outcome:
     * the confirmation page on success, or back with the domain message on a
     * rule violation.
     *
     * @param  callable(string): mixed  $checkout
     */
    private function place(Request $request, callable $checkout): RedirectResponse
    {
        try {
            $checkout(CurrentShopper::id($request));
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect('/orders/confirmation');
    }
}
