<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="pg-display text-3xl text-[color:var(--pg-ink)]">
                    Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }},
                    {{ explode(' ', auth()->user()->name)[0] }}
                </h1>
                <p class="text-sm text-[color:var(--pg-muted)] mt-1">
                    Here is how your credit position looks today
                </p>
            </div>
            <div class="flex items-center gap-3">
                <span class="pg-chip">Last 6 months</span>
                <a href="#" class="pg-btn">Export report</a>
            </div>
        </div>
    </x-slot>

    <div class="pb-14 pt-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

    @if ($empty ?? false)
        <div class="pg-card p-10 text-center text-[color:var(--pg-muted)]">
            Finish setting up your business profile to see the dashboard.
        </div>
    @else

        {{-- Hero row --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            {{-- The anchor card --}}
            <div class="pg-card-dark lg:col-span-2 p-7 flex flex-col">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="pg-label" style="color:#8FB3A8">Money owed to you</p>
                        <p class="pg-display text-4xl mt-2" style="color:#F2F7F4">KES {{ $receivables }}</p>
                        <p class="text-sm mt-1" style="color:#9DBCB1">
                            across {{ $outstandingCount }} unpaid {{ Str::plural('invoice', $outstandingCount) }}
                        </p>
                    </div>
                    <span class="text-xs px-3 py-1.5 rounded-full"
                          style="background:#2C5048; color:#B7D3C9">Receivables</span>
                </div>

                <div class="mt-6 h-40"><canvas id="trendChart"></canvas></div>

                <p class="text-xs mt-2" style="color:#7FA396">Average days late per month</p>

                <div class="mt-5 pt-5 grid grid-cols-3 gap-4" style="border-top:1px solid #2C5048">
                    <div>
                        <p class="pg-label" style="color:#8FB3A8">Avg days late</p>
                        <p class="text-xl font-semibold mt-1" style="color:#F2F7F4">
                            {{ $avgDaysLate !== null ? $avgDaysLate : 'none' }}
                        </p>
                    </div>
                    <div>
                        <p class="pg-label" style="color:#8FB3A8">Settled late</p>
                        <p class="text-xl font-semibold mt-1" style="color:#F2F7F4">{{ $latePayments }}</p>
                    </div>
                    <div>
                        <p class="pg-label" style="color:#8FB3A8">Invoices settled</p>
                        <p class="text-xl font-semibold mt-1" style="color:#F2F7F4">{{ $settledCount }}</p>
                    </div>
                </div>
            </div>

            {{-- Risk split --}}
            <div class="pg-card p-7">
                <h2 class="pg-display text-xl">Risk split</h2>
                <p class="text-sm text-[color:var(--pg-muted)] mt-1">{{ $clientsCount }} clients screened</p>

                <div class="h-44 mt-5"><canvas id="tierChart"></canvas></div>

                <div class="mt-6 space-y-3">
                    @foreach (['Low' => 'low', 'Medium' => 'medium', 'High' => 'high'] as $tier => $slug)
                        <div class="flex items-center justify-between">
                            <span class="pg-pill pg-pill--{{ $slug }}">{{ $tier }}</span>
                            <span class="text-lg font-semibold">{{ $tierCounts[$tier] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Soft tiles --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">
            <div class="pg-tile pg-tile--sage">
                <p class="pg-label">Revenue received</p>
                <p class="pg-figure mt-2">KES {{ $revenue }}</p>
            </div>
            <div class="pg-tile pg-tile--sand">
                <p class="pg-label">Expenses incurred</p>
                <p class="pg-figure mt-2">KES {{ $expenses }}</p>
            </div>
            <div class="pg-tile pg-tile--mist">
                <p class="pg-label">Clients screened</p>
                <p class="pg-figure mt-2">{{ $clientsCount }}</p>
            </div>
            <div class="pg-tile pg-tile--clay">
                <p class="pg-label">High risk clients</p>
                <p class="pg-figure mt-2">{{ $tierCounts['High'] }}</p>
            </div>
        </div>

        {{-- Clients by risk --}}
        <div class="pg-card overflow-hidden">
            <div class="px-7 pt-6 pb-4 flex items-center justify-between">
                <div>
                    <h2 class="pg-display text-xl">Clients by risk</h2>
                    <p class="text-sm text-[color:var(--pg-muted)] mt-1">Highest risk first</p>
                </div>
                <a href="{{ route('clients.index') }}" class="pg-chip">View all clients</a>
            </div>

            <table class="min-w-full pg-table">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Sector</th>
                        <th class="text-right">Invoices</th>
                        <th class="text-right">Avg days late</th>
                        <th>Risk tier</th>
                        <th>Recommendation</th>
                        <th class="text-right">Chance late</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($assessments as $row)
                    @php
                        $slug = strtolower($row['tier'] ?? 'unknown');
                    @endphp
                    <tr>
                        <td class="font-semibold">
                            <a href="{{ route('invoices.risk', $row['invoice']) }}" class="hover:underline">
                                {{ $row['client']->client_name }}
                            </a>
                        </td>
                        <td class="text-[color:var(--pg-muted)]">{{ $row['client']->sector ?: '-' }}</td>
                        <td class="text-right">{{ $row['invoices'] }}</td>
                        <td class="text-right">
                            {{ $row['avg_days_late'] !== null ? $row['avg_days_late'] : '-' }}
                        </td>
                        <td><span class="pg-pill pg-pill--{{ $slug }}">{{ $row['tier'] ?? 'Not assessed' }}</span></td>
                        <td class="text-[color:var(--pg-muted)]">{{ Str::limit($row['recommendation'], 54) }}</td>
                        <td class="text-right font-semibold">
                            {{ $row['confidence'] !== null ? $row['confidence'] . '%' : '-' }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-[color:var(--pg-muted)] py-12">
                        No clients with invoices yet. Add a client and record an invoice to see risk ratings.
                    </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <p class="text-xs text-[color:var(--pg-muted)] px-1">
            Risk tiers come from the machine learning service and refresh every ten minutes.
            Chance late is the calibrated probability of settling after the due date not a measure of certainty.
        </p>

        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script>
            Chart.defaults.font.family = "'Figtree', system-ui, sans-serif";

            new Chart(document.getElementById('trendChart'), {
                type: 'line',
                data: {
                    labels: @json($trendLabels),
                    datasets: [{
                        data: @json($trendValues),
                        borderColor: '#9FD2BF',
                        backgroundColor: 'rgba(159,210,191,0.14)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        spanGaps: true,
                        pointRadius: 3,
                        pointBackgroundColor: '#C9E7DA',
                        pointBorderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, border: { display: false },
                             ticks: { color: '#7FA396', font: { size: 11 } } },
                        y: { beginAtZero: true,
                             grid: { color: 'rgba(255,255,255,0.07)' }, border: { display: false },
                             ticks: { color: '#7FA396', font: { size: 11 } } }
                    }
                }
            });

            new Chart(document.getElementById('tierChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Low', 'Medium', 'High'],
                    datasets: [{
                        data: [{{ $tierCounts['Low'] }}, {{ $tierCounts['Medium'] }}, {{ $tierCounts['High'] }}],
                        backgroundColor: ['#8FBFA6', '#E0BE7A', '#C98574'],
                        borderWidth: 3,
                        borderColor: '#FFFFFF'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    plugins: { legend: { display: false } }
                }
            });
        </script>
    @endif
    </div>
</x-app-layout>