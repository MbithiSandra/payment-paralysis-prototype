<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'PayGuard') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased pg-canvas">
<div x-data="{ open: false }" class="min-h-screen lg:flex">

    {{-- Sidebar --}}
    <aside class="pg-side w-60 shrink-0 fixed inset-y-0 left-0 z-40 flex flex-col
                  transform transition-transform lg:static lg:translate-x-0"
           :class="open ? 'translate-x-0' : '-translate-x-full'">

        <div class="flex items-center gap-3 px-6 pt-7 pb-8">
            <div class="pg-side-mark w-9 h-9 flex items-center justify-center font-bold">P</div>
            <div>
                <p class="text-white font-semibold text-[15px] leading-tight">SME Credit Screening</p>
            </div>
        </div>

        <nav class="flex-1">
            <a href="{{ route('dashboard') }}"
               class="pg-side-link {{ request()->routeIs('dashboard') ? 'pg-side-link--active' : '' }}">Dashboard</a>

            <a href="{{ route('clients.index') }}"
               class="pg-side-link {{ request()->routeIs('clients.*') ? 'pg-side-link--active' : '' }}">Clients</a>

            <a href="{{ route('invoices.index') }}"
               class="pg-side-link {{ request()->routeIs('invoices.index') || request()->routeIs('invoices.create') || request()->routeIs('invoices.edit') ? 'pg-side-link--active' : '' }}">Invoices</a>

            <a href="{{ route('budgets.index') }}"
               class="pg-side-link {{ request()->routeIs('budgets.*') ? 'pg-side-link--active' : '' }}">Budget</a>

            <a href="{{ route('decisions.index') }}"
               class="pg-side-link {{ request()->routeIs('decisions.*') || request()->routeIs('invoices.risk') ? 'pg-side-link--active' : '' }}">Assessments</a>

            <span class="pg-side-link pg-side-link--muted">
                Reports
                <span class="ml-auto text-[9px] px-2 py-0.5 rounded-full"
                      style="background:#27463F; color:#7FB3AC">soon</span>
            </span>
        </nav>

        <div class="px-6 py-6" style="border-top:1px solid #27463F">
            <p class="text-white text-[13px] font-medium truncate">{{ auth()->user()->name }}</p>
            <p class="text-[11px] truncate" style="color:#7FB3AC">{{ auth()->user()->email }}</p>
            <div class="flex items-center gap-3 mt-3 text-[11px]">
                <a href="{{ route('profile.edit') }}" style="color:#B9D3CF" class="hover:text-white">Profile</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" style="color:#B9D3CF" class="hover:text-white">Log out</button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Backdrop on small screens --}}
    <div x-show="open" x-cloak @click="open = false"
         class="fixed inset-0 bg-black/30 z-30 lg:hidden"></div>

    {{-- Main --}}
    <div class="flex-1 min-w-0">
        <div class="lg:hidden flex items-center gap-3 px-4 py-3" style="border-bottom:1px solid var(--pg-line)">
            <button @click="open = true" class="text-xl leading-none">&#9776;</button>
            <span class="font-semibold">PayGuard</span>
        </div>

        @isset($header)
            <header class="max-w-7xl mx-auto pt-10 px-4 sm:px-6 lg:px-8">
                {{ $header }}
            </header>
        @endisset

        <main>{{ $slot }}</main>
    </div>
</div>
</body>
</html>