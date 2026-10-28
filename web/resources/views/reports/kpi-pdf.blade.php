<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>KPI report</title>
    <style>
        @page { margin: 36px 40px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #1E2A26; }
        h1, h2 { font-family: DejaVu Serif, serif; font-weight: normal; margin: 0; }
        h1 { font-size: 22px; }
        h2 { font-size: 14px; margin-bottom: 8px; }
        .muted { color: #7A7468; }
        .small { font-size: 9px; }
        .section { margin-top: 18px; }

        .anchor { background: #1F3A34; color: #F2F7F4; border-radius: 14px; padding: 16px 20px; }
        .anchor .label { color: #8FB3A8; font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.6px; }
        .anchor .big { font-family: DejaVu Serif, serif; font-size: 24px; margin-top: 4px; }
        .anchor .sub { color: #9DBCB1; }

        .tiles { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin: 0 -8px; }
        .tile { border-radius: 12px; padding: 12px 14px; vertical-align: top; }
        .tile .label { font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.6px; color: #5E5A50; }
        .tile .fig { font-size: 16px; margin-top: 4px; font-weight: bold; }
        .sage { background: #DDE8DF; } .sand { background: #EFE4CF; }
        .mist { background: #E1E8EC; } .clay { background: #F0DCD3; }

        table.data { width: 100%; border-collapse: collapse; }
        table.data th { text-align: left; font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.5px;
                        color: #7A7468; border-bottom: 1px solid #E6DDCF; padding: 6px 6px; }
        table.data td { border-bottom: 1px solid #EFE8DC; padding: 6px 6px; }
        .right { text-align: right; }

        .pill { padding: 2px 8px; border-radius: 10px; font-size: 9px; }
        .pill-low { background: #DCEBE2; color: #2F5E47; }
        .pill-medium { background: #F3E6C8; color: #7A5A1E; }
        .pill-high { background: #F0D6CE; color: #8A3E2C; }
        .pill-none { background: #ECE7DE; color: #7A7468; }

        .footer { margin-top: 22px; padding-top: 10px; border-top: 1px solid #E6DDCF; }
    </style>
</head>
<body>

    <table style="width:100%">
        <tr>
            <td>
                <h1>Credit position report</h1>
                <p class="muted" style="margin:4px 0 0">{{ $ownerName }} &middot; {{ $periodLabel }}</p>
            </td>
            <td class="right muted small" style="vertical-align:top">
                Generated {{ $generatedAt->format('d M Y, H:i') }}<br>
                Period {{ $from->format('d M Y') }} to {{ $to->format('d M Y') }}
            </td>
        </tr>
    </table>

    {{-- Anchor --}}
    <div class="anchor section">
        <table style="width:100%">
            <tr>
                <td style="width:40%; vertical-align:top">
                    <div class="label">Money owed to you today</div>
                    <div class="big">KES {{ $receivables }}</div>
                    <div class="sub">across {{ $outstandingCount }} unpaid {{ Str::plural('invoice', $outstandingCount) }}</div>
                </td>
                <td style="width:20%; vertical-align:top">
                    <div class="label">Avg days late</div>
                    <div style="font-size:16px; margin-top:4px">{{ $avgDaysLate ?? 'none' }}</div>
                </td>
                <td style="width:20%; vertical-align:top">
                    <div class="label">Settled late</div>
                    <div style="font-size:16px; margin-top:4px">{{ $latePayments }}</div>
                </td>
                <td style="width:20%; vertical-align:top">
                    <div class="label">Invoices settled</div>
                    <div style="font-size:16px; margin-top:4px">{{ $settledCount }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Tiles --}}
    <table class="tiles section">
        <tr>
            <td class="tile sage"><div class="label">Revenue received</div><div class="fig">KES {{ $revenue }}</div></td>
            <td class="tile sand"><div class="label">Expenses incurred</div><div class="fig">KES {{ $expenses }}</div></td>
            <td class="tile mist"><div class="label">Clients screened</div><div class="fig">{{ $clientsCount }}</div></td>
            <td class="tile clay"><div class="label">High risk clients</div><div class="fig">{{ $tierCounts['High'] }}</div></td>
        </tr>
    </table>

    {{-- Risk split and trend side by side --}}
    <table style="width:100%" class="section">
        <tr>
            <td style="width:38%; vertical-align:top; padding-right:12px">
                <h2>Risk split today</h2>
                <table class="data">
                    @foreach (['Low' => 'low', 'Medium' => 'medium', 'High' => 'high'] as $tier => $slug)
                        <tr>
                            <td><span class="pill pill-{{ $slug }}">{{ $tier }}</span></td>
                            <td class="right">{{ $tierCounts[$tier] }}</td>
                        </tr>
                    @endforeach
                </table>
            </td>
            <td style="width:62%; vertical-align:top; padding-left:12px">
                <h2>Payments by month</h2>
                <table class="data">
                    <tr><th>Month</th><th class="right">Invoices settled</th><th class="right">Avg days late</th></tr>
                    @foreach ($trend as $m)
                        <tr>
                            <td>{{ $m['label'] }}</td>
                            <td class="right">{{ $m['settled'] }}</td>
                            <td class="right">{{ $m['value'] ?? '-' }}</td>
                        </tr>
                    @endforeach
                </table>
            </td>
        </tr>
    </table>

    {{-- Clients by risk --}}
    <div class="section">
        <h2>Clients by risk</h2>
        <table class="data">
            <tr>
                <th>Client</th><th>Sector</th><th class="right">Invoices</th>
                <th class="right">Avg days late</th><th>Tier</th><th class="right">Chance late</th><th>Recommendation</th>
            </tr>
            @forelse ($assessments as $row)
                @php $slug = $row['tier'] ? strtolower($row['tier']) : 'none'; @endphp
                <tr>
                    <td><strong>{{ $row['client']->client_name }}</strong></td>
                    <td class="muted">{{ $row['client']->sector ?: '-' }}</td>
                    <td class="right">{{ $row['invoices'] }}</td>
                    <td class="right">{{ $row['avg_days_late'] ?? '-' }}</td>
                    <td><span class="pill pill-{{ $slug }}">{{ $row['tier'] ?? 'Not assessed' }}</span></td>
                    <td class="right">{{ $row['confidence'] !== null ? $row['confidence'] . '%' : '-' }}</td>
                    <td class="muted">{{ $row['recommendation'] }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">No clients with invoices yet.</td></tr>
            @endforelse
        </table>
    </div>

    <div class="footer muted small">
        Money owed and risk tiers show the position on the day the report was generated. Payment figures,
        revenue and expenses cover the chosen period. Chance late is the calibrated probability of settling
        after the due date, not a measure of certainty. Risk factors are linked to late payment in the
        training data and do not prove cause.
    </div>
</body>
</html>