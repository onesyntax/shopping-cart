@extends('layouts.app')

@section('title', 'Order Form')

@section('content')
    <div class="reveal flex items-end justify-between gap-6 mb-10 border-b border-rule pb-6">
        <div>
            <p class="text-[11px] uppercase tracking-[0.4em] text-vermilion mb-3">The order form</p>
            <h1 class="font-display text-5xl lg:text-6xl font-semibold">Your cart</h1>
        </div>
        <a href="{{ route('catalog.index') }}" class="ink-draw text-sm uppercase tracking-[0.2em] text-bone-dim hover:text-bone transition-colors pb-1">
            ← Keep shopping
        </a>
    </div>

    @if ($cart->isEmpty())
        <div class="reveal border border-rule bg-surface px-8 py-20 text-center" style="animation-delay: 100ms">
            <p class="font-display text-4xl mb-3">Your cart is empty</p>
            <p class="text-bone-dim mb-8">Nothing set in the forme just yet. The catalogue awaits.</p>
            <a href="{{ route('catalog.index') }}"
               class="inline-block bg-bone px-7 py-3 text-sm uppercase tracking-[0.2em] text-ink font-medium hover:bg-vermilion transition-colors">
                Browse the shop
            </a>
        </div>
    @else
        <div class="reveal border border-rule bg-surface" style="animation-delay: 100ms">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b-2 border-rule-strong text-[11px] uppercase tracking-[0.25em] text-bone-dim">
                        <th class="px-6 py-4 font-medium">Item</th>
                        <th class="px-6 py-4 font-medium text-center">Unit</th>
                        <th class="px-6 py-4 font-medium text-center">Qty</th>
                        <th class="px-6 py-4 font-medium text-right">Subtotal</th>
                        <th class="px-6 py-4"><span class="sr-only">Remove</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($cart->lines() as $line)
                        <tr class="border-b border-rule/70 last:border-0 hover:bg-surface-2 transition-colors">
                            <td class="px-6 py-5 font-display text-2xl">{{ $line->itemName }}</td>
                            <td class="px-6 py-5 text-center text-bone-dim tabular-nums">{{ $line->unitPrice->format() }}</td>
                            <td class="px-6 py-5 text-center tabular-nums">{{ $line->quantity->value }}</td>
                            <td class="px-6 py-5 text-right font-medium tabular-nums">{{ $line->subtotal()->format() }}</td>
                            <td class="px-6 py-5 text-right">
                                <form method="POST" action="{{ route('cart.items.remove') }}">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="item_name" value="{{ $line->itemName }}">
                                    <button type="submit"
                                            class="text-[11px] uppercase tracking-[0.2em] text-bone-dim hover:text-vermilion transition-colors">
                                        Remove
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-vermilion">
                        <td colspan="3" class="px-6 py-6 text-[11px] uppercase tracking-[0.3em] text-bone-dim">Total due</td>
                        <td class="px-6 py-6 text-right font-display text-3xl font-semibold tabular-nums">{{ $cart->total()->format() }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="mt-8 flex justify-end">
            <a href="{{ route('checkout.show') }}"
               class="inline-block bg-vermilion px-9 py-3.5 text-sm uppercase tracking-[0.2em] text-ink font-semibold hover:bg-vermilion-deep transition-colors">
                Proceed to checkout →
            </a>
        </div>
    @endif
@endsection
