<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="pg-display text-3xl">Reminders</h1>
            <p class="text-sm text-[color:var(--pg-muted)] mt-1">
                Medium and high risk invoices that are overdue or due in the next 7 days
            </p>
        </div>
    </x-slot>

    <div class="pb-14 pt-8 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

        @forelse ($rows as $row)
            <div class="pg-card p-7" x-data="{ copied: false }">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="font-semibold text-lg">{{ $row['client']->client_name }}</p>
                        <p class="text-sm text-[color:var(--pg-muted)] mt-1">
                            Invoice {{ $row['invoice']->invoice_number }} &middot;
                            KES {{ number_format($row['invoice']->amount, 2) }} &middot;
                            due {{ $row['invoice']->due_date->format('d M Y') }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($row['overdue'])
                            <span class="pg-pill pg-pill--high">Overdue {{ $row['days'] }} {{ Str::plural('day', $row['days']) }}</span>
                        @elseif ($row['days'] === 0)
                            <span class="pg-pill pg-pill--medium">Due today</span>
                        @else
                            <span class="pg-pill pg-pill--medium">Due in {{ $row['days'] }} {{ Str::plural('day', $row['days']) }}</span>
                        @endif
                        <span class="pg-pill pg-pill--{{ strtolower($row['tier']) }}">{{ $row['tier'] }} risk</span>
                    </div>
                </div>

                <div class="pg-tile pg-tile--sand mt-5">
                    <p class="pg-label">Reminder message</p>
                    <p class="text-sm mt-2 leading-relaxed" x-ref="message">{{ $row['english'] }}</p>
                    <div class="flex flex-wrap items-center gap-2 mt-4">
                        <button type="button" class="pg-chip"
                                @click="navigator.clipboard.writeText($refs.message.innerText); copied = true; setTimeout(() => copied = false, 2000)">
                            <span x-text="copied ? 'Copied' : 'Copy'"></span>
                        </button>
                        @if ($row['phone'])
                            <a href="https://wa.me/{{ $row['phone'] }}?text={{ rawurlencode($row['english']) }}"
                               target="_blank" class="pg-chip">WhatsApp</a>
                        @endif
                    </div>
                </div>

                @unless ($row['phone'])
                    <p class="text-xs text-[color:var(--pg-muted)] mt-3">
                        Add a phone number to this client to send by WhatsApp in one click.
                    </p>
                @endunless
            </div>
        @empty
            <div class="pg-card p-10 text-center text-[color:var(--pg-muted)]">
                Nothing to chase right now. No medium or high risk invoices are overdue or due in the next 7 days.
            </div>
        @endforelse
    </div>
</x-app-layout>