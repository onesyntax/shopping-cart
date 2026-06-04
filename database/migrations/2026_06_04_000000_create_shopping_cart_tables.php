<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Persistence for the shopping-cart domain. These tables are an Infrastructure
 * detail: the Eloquent-backed repositories map between them and the framework-
 * free Domain entities. Money is stored in integer cents, never floats.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_items', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description');
            $table->integer('price_cents');
            $table->timestamps();
        });

        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->string('owner_id')->unique();
            $table->timestamps();
        });

        Schema::create('cart_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained('carts')->cascadeOnDelete();
            $table->string('item_name');
            $table->integer('unit_price_cents');
            $table->integer('quantity');
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('owner_id')->index();
            $table->string('payment_method');
            $table->string('status');
            $table->string('payment_reference')->nullable();
            $table->string('deposit_reference')->nullable();
            $table->string('deposit_date')->nullable();
            $table->timestamps();
        });

        Schema::create('order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('item_name');
            $table->integer('unit_price_cents');
            $table->integer('quantity');
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('reference');
            $table->string('order_reference')->unique();
            $table->integer('total_cents');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('order_lines');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('cart_lines');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('catalog_items');
    }
};
