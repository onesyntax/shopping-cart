<?php

declare(strict_types=1);

namespace App\Checkout\Infrastructure\Http;

use App\Cart\UseCases\ViewCart;
use App\Checkout\UseCases\CheckoutByBankDeposit;
use App\Checkout\UseCases\CheckoutByCard;
use App\Checkout\UseCases\CheckoutByCashOnDelivery;
use App\Checkout\UseCases\CheckoutByCashOnHand;
use App\Checkout\UseCases\Inputs\CheckoutByBankDepositInput;
use App\Checkout\UseCases\Inputs\CheckoutByCardInput;
use App\Checkout\UseCases\Inputs\CheckoutInput;
use App\Foundation\Domain\DomainException;
use App\Foundation\Infrastructure\Http\CurrentShopper;
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
