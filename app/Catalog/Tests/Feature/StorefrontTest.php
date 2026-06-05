<?php

declare(strict_types=1);

use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(CatalogSeeder::class);
});

it('shows the catalog on the storefront home', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Hardcover notebook')
        ->assertSee('A4 hardcover notebook with 200 ruled pages')
        ->assertSee('$100.00')
        ->assertSee('Fountain pen');
});
