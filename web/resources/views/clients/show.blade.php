<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $client->client_name }}
            <span class="font-mono text-gray-500">({{ $client->client_code }})</span>
        </h2>
    </x-slot>

    <div class="py-8 max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @include('partials.status')

        <div class="bg-white shadow-sm sm:rounded-lg p-6 grid gap-4 sm:grid-cols-3 text-sm">
            <div><div class="text-gray-500">Contact</div><div>{{ $client->contact_person ?: '-' }}</div></div>
            <div><div class="text-gray-500">Phone</div><div>{{ $client->phone ?: '-' }}</div></div>
            <div><div class="text-gray-500">Email</div><div>{{ $client->email ?: '-' }}</div></div>
            <div><div class="text-gray-500">Sector</div><div>{{ $client->sector ?: '-' }}</div></div>
            <div><div class="text-gray-500">Client KRA PIN</div><div>{{ $client->client_kra_pin ?: '-' }}</div></div>
            <div><div class="text-gray-500">Client since</div>
                 <div>{{ $client->relationship_start_date?->format('d M Y') ?: '-' }}</div></div>
        </div>

        <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b font-semibold">Payment history</div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Invoice</th>
                        <th class="px-4 py-3 text-right font-semibold">Amount</th>
                        <th class="px-4 py-3 text-left font-semibold">Issued</th>
                        <th class="px-4 py-3 text-left font-semibold">Due</th>
                        <th class="px-4 py-3 text-left font-semibold">Paid</th>
                        <th class="px-4 py-3 text-left font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($client->invoices as $inv)
                        <tr>
                            <td class="px-4 py-3 font-mono">{{ $inv->invoice_number }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($inv->amount, 2) }}</td>
                            <td class="px-4 py-3">{{ $inv->issue_date->format('d M Y') }}</td>
                            <td class="px-4 py-3">{{ $inv->due_date->format('d M Y') }}</td>
                            <td class="px-4 py-3">{{ $inv->payment_date?->format('d M Y') ?: '-' }}</td>
                            <td class="px-4 py-3">
                                <span @class([
                                    'px-2 py-1 rounded-md text-xs',
                                    'bg-green-100 text-green-800' => $inv->status === 'Paid on time',
                                    'bg-red-100 text-red-800'     => in_array($inv->status, ['Paid late', 'Overdue']),
                                    'bg-gray-100 text-gray-700'   => $inv->status === 'Outstanding',
                                ])>
                                    {{ $inv->status }}
                                    @if ($inv->days_late > 0) ({{ $inv->days_late }} days) @endif
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">
                            No invoices recorded for this client yet.
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>