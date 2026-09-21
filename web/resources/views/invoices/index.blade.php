<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Invoices</h2>
            <a href="{{ route('invoices.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-xs font-semibold uppercase tracking-widest rounded-md hover:bg-gray-700">
                Record invoice
            </a>
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8">
        @include('partials.status')

        <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Invoice</th>
                        <th class="px-4 py-3 text-left font-semibold">Client</th>
                        <th class="px-4 py-3 text-right font-semibold">Amount</th>
                        <th class="px-4 py-3 text-left font-semibold">Issued</th>
                        <th class="px-4 py-3 text-left font-semibold">Due</th>
                        <th class="px-4 py-3 text-left font-semibold">Paid</th>
                        <th class="px-4 py-3 text-left font-semibold">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($invoices as $inv)
                        <tr>
                            <td class="px-4 py-3 font-mono">{{ $inv->invoice_number }}</td>
                            <td class="px-4 py-3">{{ $inv->client->client_name }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($inv->amount, 2) }}</td>
                            <td class="px-4 py-3">{{ $inv->issue_date->format('d M Y') }}</td>
                            <td class="px-4 py-3">{{ $inv->due_date->format('d M Y') }}</td>
                            <td class="px-4 py-3">{{ $inv->payment_date?->format('d M Y') ?: '-' }}</td>
                            <td class="px-4 py-3">
                                {{ $inv->status }}
                                @if ($inv->days_late > 0)
                                    <span class="text-red-600">({{ $inv->days_late }}d)</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('invoices.edit', $inv) }}" class="text-indigo-600 hover:underline">Edit</a>
                                <form action="{{ route('invoices.destroy', $inv) }}" method="POST" class="inline ml-3"
                                      onsubmit="return confirm('Remove this invoice?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">
                            No invoices yet. Record one to start building payment history.
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $invoices->links() }}</div>
    </div>
</x-app-layout>