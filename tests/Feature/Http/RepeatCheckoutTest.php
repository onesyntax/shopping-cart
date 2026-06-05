<?php

declare(strict_types=1);

use App\Domain\Shared\ReferenceGenerator;
use App\Infrastructure\Payment\FakeCardPaymentGateway;
use App\Infrastructure\Persistence\Eloquent\Models\OrderRecord;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(CatalogSeeder::class);
});

it('lets the same shopper place several orders across requests without colliding references', function () {
    // First order.
    $this->post('/cart/items', ['item_name' => 'Hardcover notebook', 'quantity' => 1]);
    $this->post('/checkout/card', ['card_token' => FakeCardPaymentGateway::VALID])
        ->assertRedirect('/orders/confirmation');

    // Drop the singleton to stand in for a fresh process/request: an in-memory
    // counter would reset here and regenerate ORD-1, colliding on save.
    $this->app->forgetInstance(ReferenceGenerator::class);

    // Second order.
    $this->post('/cart/items', ['item_name' => 'Ballpoint pen', 'quantity' => 1]);
    $this->post('/checkout/card', ['card_token' => FakeCardPaymentGateway::VALID])
        ->assertRedirect('/orders/confirmation');

    $references = OrderRecord::query()->pluck('reference');

    expect($references)->toHaveCount(2)
        ->and($references->unique())->toHaveCount(2);
});
