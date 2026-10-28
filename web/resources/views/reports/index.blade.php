<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="pg-display text-3xl">Reports</h1>
                <p class="text-sm text-[color:var(--pg-muted)] mt-1">
                    Download your credit position for {{ strtolower($periodLabel ?? 'last 6 months') }}
                </p>
            </div>
            @include('partials.period-filter', ['target' => 'reports.index'])
        </div>
    </x-slot>

    <div class="pb-14 pt-8 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

    @if ($empty ?? false)
        <div class="pg-card p-10 text-center text-[color:var(--pg-muted)]">
            Finish setting up your business profile to see reports.
        </div>
    @else
        @php
            $rowsInPeriod = $invoiceList->filter(fn ($i) => $i->issue_date->between($from, $to))->count();
        @endphp

        {{-- What the report will contain --}}
        <div class="pg-card-dark p-7">
            <p class="pg-label" style="color:#8FB3A8">Report preview &middot; {{ $periodLabel }}</p>
            <div class="mt-4 grid grid-cols-2 lg:grid-cols-4 gap-6">
                <div>
                    <p class="pg-label" style="color:#8FB3A8">Money owed today</p>
                    <p class="pg-display text-3xl mt-1" style="color:#F2F7F4">KES {{ $receivables }}</p>
                </div>
                <div>
                    <p class="pg-label" style="color:#8FB3A8">Invoices settled</p>
                    <p class="text-2xl font-semibold mt-1" style="color:#F2F7F4">{{ $settledCount }}</p>
                </div>
                <div>
                    <p class="pg-label" style="color:#8FB3A8">Settled late</p>
                    <p class="text-2xl font-semibold mt-1" style="color:#F2F7F4">{{ $latePayments }}</p>
                </div>
                <div>
                    <p class="pg-label" style="color:#8FB3A8">High risk clients</p>
                    <p class="text-2xl font-semibold mt-1" style="color:#F2F7F4">{{ $tierCounts['High'] }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            {{-- PDF --}}
            <div class="pg-card p-7 flex flex-col">
                <div class="pg-tile pg-tile--sand inline-block self-start px-3 py-1 text-xs">PDF</div>
                <h2 class="pg-display text-2xl mt-4">KPI report</h2>
                <p class="text-sm text-[color:var(--pg-muted)] mt-2 leading-relaxed">
                    A one page summary to print, file or send to a lender or partner.
                </p>
                <ul class="text-sm mt-4 space-y-2">
                    <li>&middot; Money owed and payment figures for the period</li>
                    <li>&middot; Revenue and expenses</li>
                    <li>&middot; Risk split and payments by month</li>
                    <li>&middot; Every client with tier, chance late and recommendation</li>
                </ul>
                <div class="mt-auto pt-6">
                    <a href="{{ route('dashboard.pdf', request()->query()) }}" class="pg-btn">Download PDF</a>
                </div>
            </div>

            {{-- Power BI --}}
            <div class="pg-card p-7 flex flex-col">
                <div class="pg-tile pg-tile--mist inline-block self-start px-3 py-1 text-xs">CSV for Power BI</div>
                <h2 class="pg-display text-2xl mt-4">Power BI data</h2>
                <p class="text-sm text-[color:var(--pg-muted)] mt-2 leading-relaxed">
                    One row per invoice issued in the period, {{ $rowsInPeriod }} {{ Str::plural('row', $rowsInPeriod) }},
                    with each client's risk tier attached so you can build your own charts.
                </p>
                <ol class="text-sm mt-4 space-y-2 list-decimal pl-5">
                    <li>Download the file</li>
                    <li>In Power BI Desktop choose Home, then Get data, then Text/CSV</li>
                    <li>Pick the file and click Load</li>
                </ol>
                <div class="mt-auto pt-6">
                    <a href="{{ route('dashboard.csv', request()->query()) }}" class="pg-btn">Download CSV</a>
                </div>
            </div>
        </div>

        <p class="text-xs text-[color:var(--pg-muted)] px-1">
            Money owed and risk tiers show today. Payment figures, revenue and expenses follow the chosen period.
        </p>
    @endif
    </div>
</x-app-layout>