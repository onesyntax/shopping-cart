@extends('layouts.app')

@section('title', 'The Catalogue')

@section('content')
    {{-- Specimen hero --}}
    <section class="reveal mb-16 lg:mb-24 grid grid-cols-1 lg:grid-cols-12 gap-8 items-end">
        <div class="lg:col-span-8">
            <p class="text-[11px] uppercase tracking-[0.4em] text-vermilion mb-6">A specimen of goods for the desk</p>
            <h1 class="font-display text-6xl lg:text-8xl font-semibold leading-[0.92]">
                Tools for a<br><em class="font-normal">considered</em> hand.
            </h1>
        </div>
        <div class="lg:col-span-4 lg:pb-3">
            <p class="text-bone-dim leading-relaxed border-t border-rule pt-5">
                A small, opinionated catalogue of papers, pens and inks — set and sold for those who
                still believe a letter is worth the effort.
            </p>
        </div>
    </section>

    @if (count($items) === 0)
        <p class="text-bone-dim">The catalogue is being reset. Please call again shortly.</p>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-px bg-rule border border-rule">
            @foreach ($items as $index => $item)
                <article class="reveal group relative bg-ink p-7 lg:p-8 flex flex-col min-h-[19rem] hover:bg-surface transition-colors duration-300"
                         style="animation-delay: {{ 120 + $index * 70 }}ms">
                    {{-- specimen figure + price --}}
                    <div class="flex items-start justify-between">
                        <span class="font-display text-5xl text-rule-strong group-hover:text-vermilion transition-colors duration-300 leading-none">
                            {{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}
                        </span>
                        <span class="font-display text-2xl tabular-nums">{{ $item->price->format() }}</span>
                    </div>

                    <h2 class="font-display text-3xl font-semibold mt-6 leading-[1.05]">{{ $item->name }}</h2>
                    <p class="mt-3 text-sm text-bone-dim leading-relaxed flex-1">{{ $item->description }}</p>

                    <form method="POST" action="{{ route('cart.items.add') }}" class="mt-7 flex items-stretch gap-2.5">
                        @csrf
                        <input type="hidden" name="item_name" value="{{ $item->name }}">
                        <label class="sr-only" for="qty-{{ $index }}">Quantity of {{ $item->name }}</label>
                        <input id="qty-{{ $index }}" type="number" name="quantity" value="1" min="1" step="1"
                               class="w-16 border border-rule-strong bg-ink-2 px-3 py-2.5 text-center text-sm tabular-nums focus:border-vermilion focus:outline-none">
                        <button type="submit" data-testid="add-{{ str($item->name)->slug() }}"
                                class="flex-1 bg-bone px-4 py-2.5 text-sm uppercase tracking-[0.2em] text-ink font-medium transition-colors duration-200 hover:bg-vermilion hover:text-ink">
                            Add to cart
                        </button>
                    </form>
                </article>
            @endforeach
        </div>
    @endif
@endsection
