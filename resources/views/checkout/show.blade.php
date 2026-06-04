@extends('layouts.app')

@section('title', 'Settlement')

@section('content')
    <div class="reveal flex items-end justify-between gap-6 mb-10 border-b border-rule pb-6">
        <div>
            <p class="text-[11px] uppercase tracking-[0.4em] text-vermilion mb-3">Settle the account</p>
            <h1 class="font-display text-5xl lg:text-6xl font-semibold">Checkout</h1>
        </div>
        <a href="{{ route('cart.show') }}" class="ink-draw text-sm uppercase tracking-[0.2em] text-bone-dim hover:text-bone transition-colors pb-1">
            ← Back to cart
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-10">
        {{-- Order summary --}}
        <aside class="reveal lg:col-span-2" style="animation-delay: 80ms">
            <div class="border border-rule bg-surface p-7 lg:sticky lg:top-8">
                <h2 class="text-[11px] uppercase tracking-[0.3em] text-bone-dim mb-6 pb-3 border-b border-rule">Your order</h2>
                <ul class="space-y-5">
                    @foreach ($cart->lines() as $line)
                        <li class="flex items-baseline justify-between gap-4">
                            <span class="font-display text-xl leading-tight">
                                {{ $line->itemName }}
                                <span class="text-bone-dim text-sm font-sans">× {{ $line->quantity->value }}</span>
                            </span>
                            <span class="tabular-nums shrink-0">{{ $line->subtotal()->format() }}</span>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-7 pt-5 border-t-2 border-vermilion flex items-baseline justify-between">
                    <span class="text-[11px] uppercase tracking-[0.3em] text-bone-dim">Total due</span>
                    <span class="font-display text-4xl font-semibold tabular-nums">{{ $cart->total()->format() }}</span>
                </div>
            </div>
        </aside>

        {{-- Payment methods --}}
        <div class="lg:col-span-3 space-y-5">
            <p class="reveal text-bone-dim" style="animation-delay: 140ms">How would you like to settle this order?</p>

            {{-- Card --}}
            <section class="reveal border border-rule bg-surface p-7" style="animation-delay: 200ms">
                <div class="flex items-baseline justify-between mb-1.5">
                    <h3 class="font-display text-3xl font-semibold">Card</h3>
                    <span class="text-[11px] uppercase tracking-[0.2em] text-vermilion">paid now</span>
                </div>
                <p class="text-sm text-bone-dim mb-5">Charged immediately. Your invoice is struck the moment the bank approves.</p>
                <form method="POST" action="{{ route('checkout.card') }}" class="flex flex-col sm:flex-row gap-3">
                    @csrf
                    <input type="text" name="card_token" value="valid" required
                           class="flex-1 border border-rule-strong bg-ink-2 px-4 py-3 text-sm focus:border-vermilion focus:outline-none"
                           placeholder="Card token">
                    <button type="submit"
                            class="bg-vermilion px-7 py-3 text-sm uppercase tracking-[0.2em] text-ink font-semibold hover:bg-vermilion-deep transition-colors">
                        Pay by card
                    </button>
                </form>
                <p class="mt-3 text-xs text-bone-dim">Demo gateway — tokens <code class="text-bone">valid</code>, <code class="text-bone">declined</code> or <code class="text-bone">unreachable</code>.</p>
            </section>

            {{-- Bank deposit --}}
            <section class="reveal border border-rule bg-surface p-7" style="animation-delay: 260ms">
                <div class="flex items-baseline justify-between mb-1.5">
                    <h3 class="font-display text-3xl font-semibold">Bank deposit</h3>
                    <span class="text-[11px] uppercase tracking-[0.2em] text-bone-dim">awaiting confirmation</span>
                </div>
                <p class="text-sm text-bone-dim mb-5">Already paid into our account? Record the reference and we’ll invoice once it clears.</p>
                <form method="POST" action="{{ route('checkout.bank-deposit') }}" class="space-y-3">
                    @csrf
                    <div class="flex flex-col sm:flex-row gap-3">
                        <input type="text" name="deposit_reference" required
                               class="flex-1 border border-rule-strong bg-ink-2 px-4 py-3 text-sm focus:border-vermilion focus:outline-none"
                               placeholder="Deposit reference">
                        <input type="date" name="deposit_date" required
                               class="border border-rule-strong bg-ink-2 px-4 py-3 text-sm focus:border-vermilion focus:outline-none [color-scheme:dark]">
                    </div>
                    <button type="submit"
                            class="bg-bone px-7 py-3 text-sm uppercase tracking-[0.2em] text-ink font-semibold hover:bg-vermilion transition-colors">
                        Record deposit
                    </button>
                </form>
            </section>

            {{-- Cash options --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <section class="reveal border border-rule bg-surface p-7 flex flex-col" style="animation-delay: 320ms">
                    <h3 class="font-display text-3xl font-semibold mb-1.5">Cash on delivery</h3>
                    <p class="text-sm text-bone-dim flex-1 mb-6">We send it out; you pay the courier when it arrives.</p>
                    <form method="POST" action="{{ route('checkout.cash-on-delivery') }}">
                        @csrf
                        <button type="submit"
                                class="w-full border border-rule-strong px-6 py-3 text-sm uppercase tracking-[0.2em] text-bone hover:bg-bone hover:text-ink hover:border-bone transition-colors">
                            Order, pay on delivery
                        </button>
                    </form>
                </section>

                <section class="reveal border border-rule bg-surface p-7 flex flex-col" style="animation-delay: 380ms">
                    <h3 class="font-display text-3xl font-semibold mb-1.5">Cash on hand</h3>
                    <p class="text-sm text-bone-dim flex-1 mb-6">Paying at the counter. Settled and invoiced at once.</p>
                    <form method="POST" action="{{ route('checkout.cash-on-hand') }}">
                        @csrf
                        <button type="submit"
                                class="w-full border border-rule-strong px-6 py-3 text-sm uppercase tracking-[0.2em] text-bone hover:bg-bone hover:text-ink hover:border-bone transition-colors">
                            Pay cash now
                        </button>
                    </form>
                </section>
            </div>
        </div>
    </div>
@endsection
