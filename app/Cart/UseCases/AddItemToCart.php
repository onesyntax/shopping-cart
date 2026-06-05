<?php

declare(strict_types=1);

namespace App\Cart\UseCases;

use App\Cart\Domain\Cart;
use App\Cart\Domain\CartRepository;
use App\Cart\UseCases\Inputs\AddItemToCartInput;
use App\Catalog\Domain\ItemNotInCatalog;
use App\Catalog\Domain\ItemRepository;
use App\Foundation\Domain\Quantity;

/**
 * Adds a quantity of a catalog item to a shopper's cart. The item must exist in
 * the catalog and the quantity must be a whole number of at least 1.
 */
final class AddItemToCart
{
    public function __construct(
        private readonly ItemRepository $items,
        private readonly CartRepository $carts,
    ) {}

    public function handle(AddItemToCartInput $input): Cart
    {
        $item = $this->items->findByName($input->itemName);

        if ($item === null) {
            throw ItemNotInCatalog::named($input->itemName);
        }

        $quantity = Quantity::from($input->quantity);

        $cart = $this->carts->forOwner($input->ownerId);
        $cart->addItem($item, $quantity);
        $this->carts->save($cart);

        return $cart;
    }
}
