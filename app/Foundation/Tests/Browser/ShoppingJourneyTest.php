<?php

declare(strict_types=1);

use App\Checkout\Infrastructure\Payment\FakeCardPaymentGateway;

/*
|--------------------------------------------------------------------------
| Storefront browser journey — one continuous session, told in order
|--------------------------------------------------------------------------
|
| A single end-to-end story driven through a real browser (Playwright). The
| database is reset ONCE per run, never between tests, and every request
| resolves to the same cart owner, so each test builds on the previous one:
| the catalogue is empty at the start, gets stocked through the add-items page,
| filled into a cart, edited, and checked out four different ways. See
| App\Foundation\Tests\Support\Browser\ContinuousSession for the wiring.
|
| ORDER MATTERS — the tests must run top to bottom; do not randomise the suite.
| Buttons are targeted by `data-testid` via Pest's `@` shorthand (e.g. `@pay-card`),
| so renaming a label never breaks a test; per-item hooks are slugged from the
| item name (`@add-maple-notebook`, `@remove-cedar-pencil`). Prices/statuses are
| asserted as text on purpose — they are what the customer actually sees.
|
*/

/**
 * The three items this journey stocks through the UI. Names drive the slugged
 * data-testid hooks (e.g. "Maple notebook" -> @add-maple-notebook).
 *
 * @return list<array{name: string, description: string, price: string, slug: string}>
 */
function journeyItems(): array
{
    return [
        ['name' => 'Maple notebook', 'description' => 'A5 maple-bound notebook, 160 ruled pages', 'price' => '80.00', 'slug' => 'maple-notebook'],
        ['name' => 'Cobalt ink', 'description' => '30ml bottle of deep cobalt fountain-pen ink', 'price' => '25.00', 'slug' => 'cobalt-ink'],
        ['name' => 'Cedar pencil', 'description' => 'Cedar pencil, HB, sold singly', 'price' => '10.00', 'slug' => 'cedar-pencil'],
    ];
}

/** Stock a single item through the add-items page. */
function createCatalogItem(array $item): void
{
    visit('/catalog/items/create')
        ->fill('name', $item['name'])
        ->fill('description', $item['description'])
        ->fill('price', $item['price'])
        ->click('@add-to-catalogue')
        ->assertPathIs('/')                 // store() redirects to the homepage
        ->assertSee($item['name']);
}

/** Add a catalogue item to the cart from the homepage. */
function addItemToCart(string $slug): void
{
    visit('/')
        ->click("@add-{$slug}")
        ->assertPathIs('/cart');
}

it('shows an empty catalogue to a first-time visitor', function () {
    visit('/')
        ->assertSee('The catalogue is being reset')
        ->assertDontSee('Maple notebook')
        ->assertNoJavascriptErrors()
        ->screenshot(filename: '01-empty-catalogue');
});

it('stocks three items through the add-items page and see items on the homepage', function () {
    foreach (journeyItems() as $item) {
        createCatalogItem($item);
    }

    visit('/')
        ->assertSee('Maple notebook')
        ->assertSee('Cobalt ink')
        ->assertSee('Cedar pencil')
        ->assertSee('$80.00')
        ->assertSee('$25.00')
        ->assertSee('$10.00')
        ->screenshot(filename: '02-catalogue-stocked');
});

it('still has an empty cart', function () {
    visit('/cart')
        ->assertSee('Your cart is empty')
        ->screenshot(filename: '03-cart-empty');
});

it('adds all three items to the cart', function () {
    foreach (journeyItems() as $item) {
        addItemToCart($item['slug']);
    }

    visit('/cart')
        ->assertSee('Maple notebook')
        ->assertSee('Cobalt ink')
        ->assertSee('Cedar pencil')
        ->assertSee('$115.00')              // 80 + 25 + 10
        ->assertDontSee('Your cart is empty')
        ->screenshot(filename: '04-three-items-in-cart');
});

it('removes the pencil from the cart', function () {
    visit('/cart')
        ->click('@remove-cedar-pencil')
        ->assertPathIs('/cart')
        ->assertDontSee('Cedar pencil')
        ->screenshot(filename: '05-pencil-removed');
});

it('now shows two items in the cart', function () {
    visit('/cart')
        ->assertSee('Maple notebook')
        ->assertSee('Cobalt ink')
        ->assertDontSee('Cedar pencil')
        ->assertSee('$105.00')              // 80 + 25
        ->screenshot(filename: '06-cart-two-items');
});

it('shows an error and keeps the cart when the card is declined', function () {
    visit('/checkout')
        ->fill('card_token', FakeCardPaymentGateway::DECLINED)
        ->click('@pay-card')
        ->assertSee('declined')
        ->screenshot(filename: '07-card-declined');

    // The cart survives a failed payment.
    visit('/cart')
        ->assertSee('Maple notebook')
        ->assertSee('Cobalt ink')
        ->screenshot(filename: '08-keeps-cart-items');
});

it('places a paid order and issues an invoice when the card is valid', function () {
    visit('/checkout')
        ->fill('card_token', FakeCardPaymentGateway::VALID)
        ->click('@pay-card')
        ->assertPathIs('/orders/confirmation')
        ->assertSee('Paid')
        ->assertSee('$105.00')
        ->assertSee('INV-')                 // invoice issued for a paid card order
        ->screenshot(filename: '09-card-paid');

    visit('/cart')
        ->assertSee('Your cart is empty')
        ->screenshot(filename: '10-cart-emptied');
});

it('records a bank-deposit order awaiting confirmation, then empties the cart', function () {
    addItemToCart('maple-notebook');

    visit('/cart')
        ->assertSee('Maple notebook')
        ->screenshot(filename: '11-add-cart-item');

    visit('/checkout')
        ->fill('deposit_reference', 'DEP-42')
        ->fill('deposit_date', '2026-06-04')
        ->click('@pay-bank-deposit')
        ->assertPathIs('/orders/confirmation')
        ->assertSee('DEP-42')
        ->assertSee('Awaiting')
        ->screenshot(filename: '12-bank-deposit');

    visit('/cart')
        ->assertSee('Your cart is empty')
        ->screenshot(filename: '13-cart-emptied');
});

it('settles a cash-on-hand order with an invoice, then empties the cart', function () {
    addItemToCart('cobalt-ink');

    visit('/cart')
        ->assertSee('Cobalt ink')
        ->screenshot(filename: '14-add-cart-item');

    visit('/checkout')
        ->click('@pay-cash-on-hand')
        ->assertPathIs('/orders/confirmation')
        ->assertSee('Paid')
        ->assertSee('INV-')
        ->screenshot(filename: '15-cash-on-hand');

    visit('/cart')
        ->assertSee('Your cart is empty')
        ->screenshot(filename: '16-cart-emptied');
});

it('sends a cash-on-delivery order out with an invoice, then empties the cart', function () {
    addItemToCart('cedar-pencil');

    visit('/cart')
        ->assertSee('Cedar pencil')
        ->screenshot(filename: '17-add-cart-item');

    visit('/checkout')
        ->click('@pay-cash-on-delivery')
        ->assertPathIs('/orders/confirmation')
        ->assertSee('delivery')
        ->assertSee('INV-')
        ->screenshot(filename: '18-cash-on-delivery');

    visit('/cart')
        ->assertSee('Your cart is empty')
        ->screenshot(filename: '19-cart-emptied');
});
