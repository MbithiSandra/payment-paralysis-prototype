<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="pg-display text-3xl">Edit {{ $budget->month->format('F Y') }}</h1>
                <p class="text-sm text-[color:var(--pg-muted)] mt-1">Correct the revenue or expenses for this month</p>
            </div>
            <a href="{{ route('budgets.index') }}" class="pg-chip">Back to budget</a>
        </div>
    </x-slot>

    <div class="pb-14 pt-8 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('budgets.update', $budget) }}" class="pg-card p-7 space-y-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="pg-label" for="revenue">Revenue (KES)</label>
                    <input id="revenue" name="revenue" type="number" step="0.01" min="0" required
                           value="{{ old('revenue', $budget->revenue) }}"
                           class="mt-2 w-full rounded-xl text-sm"
                           style="border:1px solid var(--pg-line); background:#fff; padding:12px 14px">
                    @error('revenue') <p class="text-xs mt-2" style="color:var(--pg-high)">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="pg-label" for="expenses">Expenses (KES)</label>
                    <input id="expenses" name="expenses" type="number" step="0.01" min="0" required
                           value="{{ old('expenses', $budget->expenses) }}"
                           class="mt-2 w-full rounded-xl text-sm"
                           style="border:1px solid var(--pg-line); background:#fff; padding:12px 14px">
                    @error('expenses') <p class="text-xs mt-2" style="color:var(--pg-high)">{{ $message }}</p> @enderror
                </div>
            </div>

            <button type="submit" class="pg-btn">Save changes</button>
        </form>
    </div>
</x-app-layout>