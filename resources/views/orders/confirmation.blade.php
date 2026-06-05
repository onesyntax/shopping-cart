@extends('layouts.app')

@section('title', 'Proof of Order')

@php
    $order = $confirmation->order;
    $statusLabel = match ($order->status) {
        \App\Domain\Checkout\OrderStatus::Paid => 'Paid',
        \App\Domain\Checkout\OrderStatus::AwaitingDepositConfirmation => 'Awaiting deposit confirmation',
        \App\Domain\Checkout\OrderStatus::AwaitingPaymentOnDelivery => 'Awaiting payment on delivery',
    };
    $isPaid = $order->isPaid();
@endphp

@section('content')
    <div class="reveal max-w-3xl">
        <p class="text-[11px] uppercase tracking-[0.4em] text-vermilion mb-5">Pulled &amp; proofed — with thanks</p>
        <h1 class="font-display text-6xl lg:text-7xl font-semibold leading-[0.95]">
            Your order is <em class="font-normal">placed</em>.
        </h1>
        <p class="mt-6 text-lg text-bone-dim leading-relaxed">
            A note confirming the details below has been sent your way. We’re grateful for your custom.
        </p>
    </div>

    <div class="mt-14 grid grid-cols-1 lg:grid-cols-3 gap-px bg-rule border border-rule">
        {{-- Order facts --}}
        <dl class="reveal lg:col-span-1 space-y-6 bg-surface p-8" style="animation-delay: 100ms">
            <div>
                <dt class="text-[11px] uppercase tracking-[0.25em] text-bone-dim">Order</dt>
                <dd class="font-display text-2xl mt-1 tabular-nums">{{ $order->reference }}</dd>
            </div>
            <div>
                <dt class="text-[11px] uppercase tracking-[0.25em] text-bone-dim">Status</dt>
                <dd class="mt-2">
                    <span class="inline-block border px-3 py-1 text-[11px] uppercase tracking-[0.18em] {{ $isPaid ? 'border-positive text-positive' : 'border-vermilion text-vermilion' }}">
                        {{ $statusLabel }}
                    </span>
                </dd>
            </div>
            <div>
                <dt class="text-[11px] uppercase tracking-[0.25em] text-bone-dim">Payment method</dt>
                <dd class="mt-1 capitalize">{{ $order->paymentMethod->label() }}</dd>
            </div>
            @if ($order->paymentReference)
                <div>
                    <dt class="text-[11px] uppercase tracking-[0.25em] text-bone-dim">Transaction</dt>
                    <dd class="mt-1 tabular-nums">{{ $order->paymentReference }}</dd>
                </div>
            @endif
            @if ($order->depositReference)
                <div>
                    <dt class="text-[11px] uppercase tracking-[0.25em] text-bone-dim">Deposit reference</dt>
                    <dd class="mt-1 tabular-nums">{{ $order->depositReference }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] uppercase tracking-[0.25em] text-bone-dim">Deposit date</dt>
                    <dd class="mt-1 tabular-nums">{{ $order->depositDate }}</dd>
                </div>
            @endif
            @if ($confirmation->invoice)
                <div>
                    <dt class="text-[11px] uppercase tracking-[0.25em] text-bone-dim">Invoice</dt>
                    <dd class="mt-1 tabular-nums">{{ $confirmation->invoice->reference }}</dd>
                </div>
            @endif
        </dl>

        {{-- Lines --}}
        <div class="reveal lg:col-span-2 bg-ink" style="animation-delay: 160ms">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b-2 border-rule-strong text-[11px] uppercase tracking-[0.25em] text-bone-dim">
                        <th class="px-7 py-4 font-medium">Item</th>
                        <th class="px-7 py-4 font-medium text-center">Qty</th>
                        <th class="px-7 py-4 font-medium text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->lines as $line)
                        <tr class="border-b border-rule/70 last:border-0">
                            <td class="px-7 py-5 font-display text-2xl">{{ $line->itemName }}</td>
                            <td class="px-7 py-5 text-center tabular-nums">{{ $line->quantity->value }}</td>
                            <td class="px-7 py-5 text-right tabular-nums">{{ $line->subtotal()->format() }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-vermilion">
                        <td colspan="2" class="px-7 py-6 text-[11px] uppercase tracking-[0.3em] text-bone-dim">Total</td>
                        <td class="px-7 py-6 text-right font-display text-3xl font-semibold tabular-nums">{{ $order->total()->format() }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="reveal mt-10" style="animation-delay: 220ms">
        <a href="{{ route('catalog.index') }}"
           class="inline-block bg-bone px-8 py-3.5 text-sm uppercase tracking-[0.2em] text-ink font-semibold hover:bg-vermilion transition-colors">
            Continue shopping
        </a>
    </div>
@endsection
