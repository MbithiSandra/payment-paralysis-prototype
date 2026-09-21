<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Monthly revenue and expenses</h2>
    </x-slot>

    <div class="py-8 max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @include('partials.status')

        <div class="bg-white shadow-sm sm:rounded-lg p-6">
            <form method="POST" action="{{ route('budgets.store') }}" class="grid gap-4 sm:grid-cols-4 sm:items-end">
                @csrf
                <div>
                    <x-input-label for="month" value="Month" />
                    <x-text-input id="month" name="month" type="month" class="block mt-1 w-full"
                                  :value="old('month', now()->format('Y-m'))" required />
                    <x-input-error :messages="$errors->get('month')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="revenue" value="Revenue (KES)" />
                    <x-text-input id="revenue" name="revenue" type="number" step="0.01" min="0"
                                  class="block mt-1 w-full" :value="old('revenue')" required />
                    <x-input-error :messages="$errors->get('revenue')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="expenses" value="Expenses (KES)" />
                    <x-text-input id="expenses" name="expenses" type="number" step="0.01" min="0"
                                  class="block mt-1 w-full" :value="old('expenses')" required />
                    <x-input-error :messages="$errors->get('expenses')" class="mt-2" />
                </div>
                <div><x-primary-button class="w-full justify-center">Save month</x-primary-button></div>
            </form>
            <p class="mt-3 text-xs text-gray-500">
                Saving a month that already exists updates it rather than adding a second row.
            </p>
        </div>

        <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Month</th>
                        <th class="px-4 py-3 text-right font-semibold">Revenue</th>
                        <th class="px-4 py-3 text-right font-semibold">Expenses</th>
                        <th class="px-4 py-3 text-right font-semibold">Net</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($budgets as $b)
                        <tr>
                            <td class="px-4 py-3">{{ $b->month->format('F Y') }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($b->revenue, 2) }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($b->expenses, 2) }}</td>
                            <td class="px-4 py-3 text-right {{ $b->net < 0 ? 'text-red-600' : 'text-green-700' }}">
                                {{ number_format($b->net, 2) }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <form action="{{ route('budgets.destroy', $b) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Remove this month?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">
                            No months recorded yet.
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $budgets->links() }}</div>
    </div>
</x-app-layout>