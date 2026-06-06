<?php

declare(strict_types=1);

use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(CatalogSeeder::class);
});

it('shows an empty cart message when nothing has been added', function () {
    $this->get('/cart')
        ->assertOk()
        ->assertSee('Your cart is empty');
});

it('adds an item to the cart and keeps it across requests', function () {
    $this->post('/cart/items', ['item_name' => 'Hardcover notebook', 'quantity' => 2])
        ->assertRedirect('/cart');

    $this->get('/cart')
        ->assertOk()
        ->assertSee('Hardcover notebook')
        ->assertSee('$200.00'); // 2 x $100.00 subtotal
});

it('merges quantities when the same item is added again', function () {
    $this->post('/cart/items', ['item_name' => 'Ballpoint pen', 'quantity' => 1]);
    $this->post('/cart/items', ['item_name' => 'Ballpoint pen', 'quantity' => 2]);

    $this->get('/cart')->assertSee('$150.00'); // 3 x $50.00
});

it('rejects a fractional quantity with the domain message and adds nothing', function () {
    $this->post('/cart/items', ['item_name' => 'Ballpoint pen', 'quantity' => '1.5'])
        ->assertRedirect();

    $this->followingRedirects()
        ->post('/cart/items', ['item_name' => 'Ballpoint pen', 'quantity' => '1.5'])
        ->assertSee('whole number');

    $this->get('/cart')->assertSee('Your cart is empty');
});

it('rejects an item that is not in the catalog', function () {
    $this->followingRedirects()
        ->post('/cart/items', ['item_name' => 'Unicorn', 'quantity' => 1])
        ->assertSee('not in the catalog');
});

it('requires an item name and quantity', function () {
    $this->post('/cart/items', [])
        ->assertSessionHasErrors(['item_name', 'quantity']);
});

it('removes an item line from the cart', function () {
    $this->post('/cart/items', ['item_name' => 'Hardcover notebook', 'quantity' => 1]);

    $this->delete('/cart/items', ['item_name' => 'Hardcover notebook'])
        ->assertRedirect('/cart');

    $this->get('/cart')->assertSee('Your cart is empty');
});
