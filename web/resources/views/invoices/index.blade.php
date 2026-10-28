<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="pg-display text-3xl">Invoices</h1>
                <p class="text-sm text-[color:var(--pg-muted)] mt-1">Every invoice raised against your clients</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('invoices.import') }}" class="pg-chip">Import CSV</a>
                <a href="{{ route('invoices.create') }}" class="pg-btn">Record invoice</a>
            </div>
        </div>
    </x-slot>

    <div class="pb-14 pt-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
        @include('partials.status')

        <div class="pg-card overflow-hidden">
            <table class="min-w-full pg-table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Client</th>
                        <th class="text-right">Amount (KES)</th>
                        <th>Issued</th>
                        <th>Due</th>
                        <th>Paid</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $inv)
                        @php
                            $pill = match ($inv->status) {
                                'Paid on time' => 'low',
                                'Outstanding'  => 'medium',
                                default        => 'high',
                            };
                        @endphp
                        <tr>
                            <td class="font-mono text-xs">{{ $inv->invoice_number }}</td>
                            <td class="font-semibold">{{ $inv->client->client_name }}</td>
                            <td class="text-right">{{ number_format($inv->amount, 2) }}</td>
                            <td>{{ $inv->issue_date->format('d M Y') }}</td>
                            <td>{{ $inv->due_date->format('d M Y') }}</td>
                            <td>{{ $inv->payment_date?->format('d M Y') ?: '-' }}</td>
                            <td>
                                <span class="pg-pill pg-pill--{{ $pill }}">{{ $inv->status }}</span>
                                @if ($inv->days_late > 0)
                                    <span class="text-xs text-[color:var(--pg-muted)] ml-1">{{ $inv->days_late }}d</span>
                                @endif
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('invoices.risk', $inv) }}" class="hover:underline font-semibold"
                                   style="color:var(--pg-accent)">Assess risk</a>
                                <a href="{{ route('invoices.edit', $inv) }}" class="hover:underline ml-3">Edit</a>
                                <form action="{{ route('invoices.destroy', $inv) }}" method="POST" class="inline ml-3"
                                      onsubmit="return confirm('Remove this invoice?')">
                                    @csrf @method('DELETE')
                                    <button class="hover:underline" style="color:var(--pg-high)">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-[color:var(--pg-muted)] py-12">
                            No invoices yet. Record one or import a CSV to start building payment history.
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $invoices->links() }}</div>
    </div>
</x-app-layout>