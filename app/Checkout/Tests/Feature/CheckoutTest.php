<?php

declare(strict_types=1);

use App\Checkout\Infrastructure\Payment\FakeCardPaymentGateway;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(CatalogSeeder::class);
});

function fillCart($test): void
{
    $test->post('/cart/items', ['item_name' => 'Hardcover notebook', 'quantity' => 2]);
}

it('redirects to the cart when checking out with nothing in it', function () {
    $this->get('/checkout')
        ->assertRedirect('/cart');
});

it('shows the payment options with the cart total once the cart has items', function () {
    fillCart($this);

    $this->get('/checkout')
        ->assertOk()
        ->assertSee('$200.00')
        ->assertSee('Card')
        ->assertSee('Bank deposit')
        ->assertSee('Cash on delivery')
        ->assertSee('Cash on hand');
});

it('places a paid order when the card is approved and empties the cart', function () {
    fillCart($this);

    $this->post('/checkout/card', ['card_token' => FakeCardPaymentGateway::VALID])
        ->assertRedirect('/orders/confirmation');

    $this->get('/orders/confirmation')
        ->assertOk()
        ->assertSee('Paid')
        ->assertSee('$200.00');

    $this->get('/cart')->assertSee('Your cart is empty');
});

it('does not place an order when the card is declined and keeps the cart', function () {
    fillCart($this);

    $this->followingRedirects()
        ->post('/checkout/card', ['card_token' => FakeCardPaymentGateway::DECLINED])
        ->assertSee('declined');

    $this->get('/cart')->assertSee('Hardcover notebook');
});

it('reports an unreachable gateway and keeps the cart', function () {
    fillCart($this);

    $this->followingRedirects()
        ->post('/checkout/card', ['card_token' => FakeCardPaymentGateway::UNREACHABLE])
        ->assertSee('could not be reached');

    $this->get('/cart')->assertSee('Hardcover notebook');
});

it('requires a card token', function () {
    fillCart($this);

    $this->post('/checkout/card', [])
        ->assertSessionHasErrors('card_token');
});

it('records a bank-deposit order awaiting confirmation', function () {
    fillCart($this);

    $this->post('/checkout/bank-deposit', ['deposit_reference' => 'DEP-42', 'deposit_date' => '2026-06-04'])
        ->assertRedirect('/orders/confirmation');

    $this->get('/orders/confirmation')
        ->assertOk()
        ->assertSee('DEP-42')
        ->assertSee('Awaiting');

    $this->get('/cart')->assertSee('Your cart is empty');
});

it('requires a deposit reference and date', function () {
    fillCart($this);

    $this->post('/checkout/bank-deposit', [])
        ->assertSessionHasErrors(['deposit_reference', 'deposit_date']);
});

it('places a cash-on-delivery order awaiting payment on delivery', function () {
    fillCart($this);

    $this->post('/checkout/cash-on-delivery')
        ->assertRedirect('/orders/confirmation');

    $this->get('/orders/confirmation')->assertSee('delivery');
    $this->get('/cart')->assertSee('Your cart is empty');
});

it('settles a cash-on-hand order as paid', function () {
    fillCart($this);

    $this->post('/checkout/cash-on-hand')
        ->assertRedirect('/orders/confirmation');

    $this->get('/orders/confirmation')->assertSee('Paid');
    $this->get('/cart')->assertSee('Your cart is empty');
});
