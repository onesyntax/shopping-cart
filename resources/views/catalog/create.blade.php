@extends('layouts.app')

@section('title', 'Add an item')

@section('content')
    <section class="reveal mb-12 lg:mb-16 grid grid-cols-1 lg:grid-cols-12 gap-8 items-end">
        <div class="lg:col-span-8">
            <p class="text-[11px] uppercase tracking-[0.4em] text-vermilion mb-6">Set a new listing</p>
            <h1 class="font-display text-6xl lg:text-8xl font-semibold leading-[0.92]">
                Add an item<br><em class="font-normal">to the bench.</em>
            </h1>
        </div>
        <div class="lg:col-span-4 lg:pb-3">
            <p class="text-bone-dim leading-relaxed border-t border-rule pt-5">
                Give it a name, a few honest words and a price. The name identifies the
                listing, so no two items may share one.
            </p>
        </div>
    </section>

    <form method="POST" action="{{ route('catalog.items.store') }}" class="reveal max-w-2xl space-y-8">
        @csrf

        <div>
            <label for="name" class="block text-[11px] uppercase tracking-[0.3em] text-bone-dim mb-3">Name</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" autofocus
                   class="w-full border border-rule-strong bg-ink-2 px-4 py-3 text-sm focus:border-vermilion focus:outline-none">
        </div>

        <div>
            <label for="description" class="block text-[11px] uppercase tracking-[0.3em] text-bone-dim mb-3">Description</label>
            <textarea id="description" name="description" rows="3"
                      class="w-full border border-rule-strong bg-ink-2 px-4 py-3 text-sm leading-relaxed focus:border-vermilion focus:outline-none">{{ old('description') }}</textarea>
        </div>

        <div>
            <label for="price" class="block text-[11px] uppercase tracking-[0.3em] text-bone-dim mb-3">Price</label>
            <input id="price" type="text" name="price" value="{{ old('price') }}" inputmode="decimal" placeholder="$0.00"
                   class="w-40 border border-rule-strong bg-ink-2 px-4 py-3 text-sm tabular-nums focus:border-vermilion focus:outline-none">
            <p class="mt-2 text-xs text-bone-dim">In dollars, e.g. <span class="tabular-nums">100.00</span>.</p>
        </div>

        <div class="flex items-center gap-4 pt-2">
            <button type="submit"
                    class="bg-bone px-6 py-3 text-sm uppercase tracking-[0.2em] text-ink font-medium transition-colors duration-200 hover:bg-vermilion hover:text-ink">
                Add to catalogue
            </button>
            <a href="{{ route('catalog.index') }}" class="ink-draw text-sm uppercase tracking-[0.2em] text-bone-dim hover:text-bone transition-colors">
                Back to shop
            </a>
        </div>
    </form>
@endsection
