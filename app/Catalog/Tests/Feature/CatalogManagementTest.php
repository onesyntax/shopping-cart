<?php

declare(strict_types=1);

use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(CatalogSeeder::class);
});

it('shows the add-item form', function () {
    $this->get('/catalog/items/create')
        ->assertOk()
        ->assertSee('Add an item');
});

it('adds a new item and makes it available in the catalog', function () {
    $this->post('/catalog/items', [
        'name' => 'Leather pencil case',
        'description' => 'Full-grain leather roll for six pencils',
        'price' => '85.00',
    ])->assertRedirect('/');

    $this->get('/')
        ->assertOk()
        ->assertSee('Leather pencil case')
        ->assertSee('Full-grain leather roll for six pencils')
        ->assertSee('$85.00');
});

it('requires a name, description and price', function () {
    $this->post('/catalog/items', [])
        ->assertSessionHasErrors(['name', 'description', 'price']);
});

it('rejects a non-positive price with the domain message and adds nothing', function () {
    $this->followingRedirects()
        ->post('/catalog/items', [
            'name' => 'Free notebook',
            'description' => 'Should never be listed',
            'price' => '0.00',
        ])
        ->assertSee('greater than zero');

    $this->get('/')->assertDontSee('Free notebook');
});

it('rejects an item whose name is already in the catalog', function () {
    $this->followingRedirects()
        ->post('/catalog/items', [
            'name' => 'Hardcover notebook',
            'description' => 'A second hardcover notebook listing',
            'price' => '120.00',
        ])
        ->assertSee('already exists');
});
