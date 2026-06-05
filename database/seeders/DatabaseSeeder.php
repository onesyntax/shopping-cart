<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database. The storefront has no user accounts, so
     * the only seed is the catalog the shopper browses.
     */
    public function run(): void
    {
        $this->call(CatalogSeeder::class);
    }
}
