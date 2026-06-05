<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'The Pressroom') — Marginalia · fine stationery</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased min-h-screen flex flex-col">
    {{-- Masthead --}}
    <header class="reveal">
        <div class="border-b border-rule-strong">
            <div class="mx-auto max-w-6xl px-6 lg:px-10">
                {{-- Issue line --}}
                <div class="flex items-center justify-between py-2.5 text-[11px] uppercase tracking-[0.35em] text-bone-dim border-b border-rule">
                    <span>No. 01 — Letterpress &amp; Sundries</span>
                    <span class="hidden sm:inline text-vermilion">Set in Bodoni &amp; Grotesk</span>
                    <span>Est. MMXXVI</span>
                </div>

                {{-- Wordmark + nav --}}
                <div class="flex items-end justify-between gap-6 py-6 lg:py-8">
                    <a href="{{ route('catalog.index') }}" class="block leading-none">
                        <span class="font-display text-5xl lg:text-7xl font-semibold tracking-tight">Marginalia</span>
                        <span class="block mt-2 text-[11px] uppercase tracking-[0.45em] text-bone-dim">Fine stationery · purveyors of the written word</span>
                    </a>
                    <nav class="flex items-center gap-7 text-sm shrink-0 pb-1">
                        <a href="{{ route('catalog.index') }}"
                           class="ink-draw uppercase tracking-[0.2em] text-bone-dim hover:text-bone transition-colors">Shop</a>
                        <a href="{{ route('cart.show') }}"
                           class="group inline-flex items-center gap-2.5 uppercase tracking-[0.2em] text-bone hover:text-vermilion transition-colors">
                            Cart
                            <span class="inline-flex h-6 min-w-6 items-center justify-center rounded-full px-1.5 text-xs font-semibold tabular-nums border {{ ($cartCount ?? 0) > 0 ? 'bg-vermilion text-ink border-vermilion' : 'border-rule-strong text-bone-dim' }}">
                                {{ $cartCount ?? 0 }}
                            </span>
                        </a>
                    </nav>
                </div>
            </div>
        </div>
        {{-- the two-rule broadsheet device --}}
        <div class="mx-auto max-w-6xl px-6 lg:px-10">
            <div class="h-1.5 border-b-2 border-vermilion"></div>
        </div>
    </header>

    @if (session('status') || session('error') || $errors->any())
        <div class="mx-auto w-full max-w-6xl px-6 lg:px-10 pt-6">
            @if (session('status'))
                <div class="reveal flex items-center gap-3 border border-rule bg-surface px-5 py-3 text-sm text-bone">
                    <span class="text-vermilion text-lg leading-none">✦</span>{{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="reveal flex items-center gap-3 border-l-2 border-vermilion bg-surface px-5 py-3 text-sm text-bone">
                    <span class="uppercase tracking-[0.2em] text-vermilion text-xs">Held</span>{{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="reveal border-l-2 border-vermilion bg-surface px-5 py-3 text-sm text-bone">
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $message)
                            <li class="flex gap-3"><span class="text-vermilion">—</span>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif

    <main class="mx-auto w-full max-w-6xl px-6 lg:px-10 py-12 lg:py-16 flex-1">
        @yield('content')
    </main>

    <footer class="border-t border-rule-strong bg-ink-2">
        <div class="mx-auto max-w-6xl px-6 lg:px-10 py-10 flex flex-col sm:flex-row items-baseline justify-between gap-3">
            <span class="font-display text-2xl">Marginalia</span>
            <span class="text-[11px] uppercase tracking-[0.3em] text-bone-dim">Pens · paper · ink · pleasant correspondence</span>
            <a href="{{ route('catalog.items.create') }}"
               class="ink-draw text-[11px] uppercase tracking-[0.3em] text-bone-dim hover:text-bone transition-colors">Add item</a>
            <span class="text-[11px] uppercase tracking-[0.3em] text-vermilion">Printed to order</span>
        </div>
    </footer>
</body>
</html>
