<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="pg-display text-3xl">Monthly budget</h1>
            <p class="text-sm text-[color:var(--pg-muted)] mt-1">Revenue received and expenses incurred each month</p>
        </div>
    </x-slot>

    <div class="pb-14 pt-8 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
        @include('partials.status')

        <form method="POST" action="{{ route('budgets.store') }}" class="pg-card p-7">
            @csrf
            <div class="grid gap-5 sm:grid-cols-4 sm:items-end">
                <div>
                    <label class="pg-label" for="month">Month</label>
                    <input id="month" name="month" type="month" required
                           max="{{ now()->format('Y-m') }}"
                           value="{{ old('month', now()->format('Y-m')) }}"
                           class="mt-2 w-full rounded-xl text-sm"
                           style="border:1px solid var(--pg-line); background:#fff; padding:12px 14px">
                    @error('month') <p class="text-xs mt-2" style="color:var(--pg-high)">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="pg-label" for="revenue">Revenue (KES)</label>
                    <input id="revenue" name="revenue" type="number" step="0.01" min="0" required
                           value="{{ old('revenue') }}"
                           class="mt-2 w-full rounded-xl text-sm"
                           style="border:1px solid var(--pg-line); background:#fff; padding:12px 14px">
                    @error('revenue') <p class="text-xs mt-2" style="color:var(--pg-high)">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="pg-label" for="expenses">Expenses (KES)</label>
                    <input id="expenses" name="expenses" type="number" step="0.01" min="0" required
                           value="{{ old('expenses') }}"
                           class="mt-2 w-full rounded-xl text-sm"
                           style="border:1px solid var(--pg-line); background:#fff; padding:12px 14px">
                    @error('expenses') <p class="text-xs mt-2" style="color:var(--pg-high)">{{ $message }}</p> @enderror
                </div>
                <div><button type="submit" class="pg-btn w-full">Save month</button></div>
            </div>
            <p class="mt-4 text-xs text-[color:var(--pg-muted)]">
                Saving a month that already exists updates it rather than adding a second row.
            </p>
        </form>

        <div class="pg-card overflow-hidden">
            <table class="min-w-full pg-table">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th class="text-right">Revenue</th>
                        <th class="text-right">Expenses</th>
                        <th class="text-right">Net</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($budgets as $b)
                        <tr>
                            <td class="font-semibold">{{ $b->month->format('F Y') }}</td>
                            <td class="text-right">{{ number_format($b->revenue, 2) }}</td>
                            <td class="text-right">{{ number_format($b->expenses, 2) }}</td>
                            <td class="text-right font-semibold"
                                style="color: {{ $b->net < 0 ? 'var(--pg-high)' : 'var(--pg-accent)' }}">
                                {{ number_format($b->net, 2) }}
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('budgets.edit', $b) }}" class="hover:underline">Edit</a>
                                <form action="{{ route('budgets.destroy', $b) }}" method="POST" class="inline ml-3"
                                      onsubmit="return confirm('Remove this month?')">
                                    @csrf @method('DELETE')
                                    <button class="hover:underline" style="color:var(--pg-high)">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-[color:var(--pg-muted)] py-12">
                            No months recorded yet.
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $budgets->links() }}</div>
    </div>
</x-app-layout>