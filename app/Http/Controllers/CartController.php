<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Cart\AddItemToCart;
use App\Application\Cart\AddItemToCartInput;
use App\Application\Cart\RemoveItemFromCart;
use App\Application\Cart\RemoveItemFromCartInput;
use App\Application\Cart\ViewCart;
use App\Domain\Shared\DomainException;
use App\Http\Requests\AddItemToCartRequest;
use App\Http\Requests\RemoveItemFromCartRequest;
use App\Http\Support\CurrentShopper;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The shopper's cart: view it, add a catalog item, remove a line. Each action
 * delegates to a single use case and translates any domain-rule violation into
 * a flash message the cart page shows.
 */
final class CartController
{
    public function show(Request $request, ViewCart $viewCart): View
    {
        return view('cart.show', [
            'cart' => $viewCart->handle(CurrentShopper::id($request)),
        ]);
    }

    public function add(AddItemToCartRequest $request, AddItemToCart $addItem): RedirectResponse
    {
        try {
            $addItem->handle(new AddItemToCartInput(
                CurrentShopper::id($request),
                $request->string('item_name')->toString(),
                $request->input('quantity'),
            ));
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect('/cart')->with('status', $request->string('item_name')->toString().' added to your cart.');
    }

    public function remove(RemoveItemFromCartRequest $request, RemoveItemFromCart $removeItem): RedirectResponse
    {
        try {
            $removeItem->handle(new RemoveItemFromCartInput(
                CurrentShopper::id($request),
                $request->string('item_name')->toString(),
            ));
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect('/cart')->with('status', 'Item removed from your cart.');
    }
}
