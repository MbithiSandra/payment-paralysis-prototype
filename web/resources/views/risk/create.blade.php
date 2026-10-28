<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="pg-display text-3xl">Assess a client</h1>
            <p class="text-sm text-[color:var(--pg-muted)] mt-1">
                Check a client before you give credit. Nothing is saved until you record a decision.
            </p>
        </div>
    </x-slot>

    <div class="pb-14 pt-8 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

        @if (session('error'))
            <div class="pg-tile pg-tile--clay text-sm">{{ session('error') }}</div>
        @endif

        @if ($clients->isEmpty())
            <div class="pg-card p-10 text-center text-[color:var(--pg-muted)]">
                Add a client first, then come back to assess them.
                <div class="mt-5"><a href="{{ route('clients.create') }}" class="pg-btn">Add client</a></div>
            </div>
        @else
            <form method="GET" action="{{ route('assess.result') }}" class="pg-card p-7 space-y-6">

                <div>
                    <label class="pg-label" for="client">Client</label>
                    <select id="client" name="client" required class="mt-2 w-full rounded-xl text-sm"
                            style="border:1px solid var(--pg-line); background:#fff; padding:12px 14px">
                        <option value="">Choose a client</option>
                        @foreach ($clients as $c)
                            <option value="{{ $c->client_code }}"
                                @selected(old('client', $selected) === $c->client_code)>
                                {{ $c->client_name }}{{ $c->sector ? ' · ' . $c->sector : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('client') <p class="text-xs mt-2" style="color:var(--pg-high)">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="pg-label" for="amount">Amount of credit (KES)</label>
                        <input id="amount" name="amount" type="number" min="1" step="0.01" required
                               value="{{ old('amount') }}" placeholder="For example 85000"
                               class="mt-2 w-full rounded-xl text-sm"
                               style="border:1px solid var(--pg-line); background:#fff; padding:12px 14px">
                        @error('amount') <p class="text-xs mt-2" style="color:var(--pg-high)">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="pg-label" for="terms_days">Days to pay</label>
                        <input id="terms_days" name="terms_days" type="number" min="0" max="365" required
                               value="{{ old('terms_days', 30) }}"
                               class="mt-2 w-full rounded-xl text-sm"
                               style="border:1px solid var(--pg-line); background:#fff; padding:12px 14px">
                        @error('terms_days') <p class="text-xs mt-2" style="color:var(--pg-high)">{{ $message }}</p> @enderror
                    </div>
                </div>

                <p class="text-xs text-[color:var(--pg-muted)]">
                    The credit is treated as given today. The client's past invoices are used to judge the risk.
                </p>

                <button type="submit" class="pg-btn">Assess risk</button>
            </form>
        @endif
    </div>
</x-app-layout>