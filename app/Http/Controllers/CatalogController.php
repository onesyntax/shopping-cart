<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Catalog\AddItemToCatalog;
use App\Application\Catalog\AddItemToCatalogInput;
use App\Application\Catalog\ListCatalog;
use App\Domain\Shared\DomainException;
use App\Http\Requests\AddItemToCatalogRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * The storefront: browse the catalog and list new items into it. Thin — each
 * action delegates to a single use case and translates any domain-rule
 * violation into a flash message.
 */
final class CatalogController
{
    public function index(ListCatalog $listCatalog): View
    {
        return view('catalog.index', ['items' => $listCatalog->handle()]);
    }

    public function create(): View
    {
        return view('catalog.create');
    }

    public function store(AddItemToCatalogRequest $request, AddItemToCatalog $addItem): RedirectResponse
    {
        try {
            $addItem->handle(new AddItemToCatalogInput(
                $request->string('name')->toString(),
                $request->string('description')->toString(),
                $request->string('price')->toString(),
            ));
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect('/')->with('status', $request->string('name')->toString().' is now in the catalogue.');
    }
}
