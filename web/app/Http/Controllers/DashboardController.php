<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Services\RiskAssessmentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public const PERIODS = [
        'month' => 'This month',
        '3m'    => 'Last 3 months',
        '6m'    => 'Last 6 months',
        'year'  => 'This year',
    ];

    public function __construct(private RiskAssessmentService $risk)
    {
    }

    public function index(Request $request)
    {
        return view('dashboard', $this->buildData($request));
    }

    /** FR11: the KPI report as a PDF. */
    public function exportPdf(Request $request)
    {
        $data = $this->buildData($request);
        abort_if($data['empty'], 404);

        $data['generatedAt'] = now();
        $data['ownerName']   = auth()->user()->name;

        return Pdf::loadView('reports.kpi-pdf', $data)
            ->setPaper('a4', 'portrait')
            ->download('kpi-report-' . now()->format('Y-m-d') . '.pdf');
    }

    /** FR11: every invoice in the period as a CSV that Power BI can open. */
    public function exportCsv(Request $request)
    {
        $data = $this->buildData($request);
        abort_if($data['empty'], 404);

        $from = $data['from'];
        $to   = $data['to'];

        $risk    = collect($data['assessments'])->keyBy(fn ($row) => $row['client']->client_code);
        $clients = $data['clientList']->keyBy('client_code');

        $invoices = $data['invoiceList']
            ->filter(fn ($i) => $i->issue_date->between($from, $to))
            ->sortBy('issue_date');

        $filename = 'powerbi-invoices-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($invoices, $clients, $risk) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");                       // so Excel and Power BI read it as UTF-8

            fputcsv($out, [
                'invoice_number', 'client_code', 'client_name', 'sector',
                'amount_kes', 'issue_date', 'due_date', 'payment_date',
                'terms_days', 'days_late', 'status',
                'client_risk_tier', 'client_chance_late_percent', 'model_used',
            ]);

            foreach ($invoices as $i) {
                $client = $clients->get($i->client_code);
                $row    = $risk->get($i->client_code);

                if ($i->payment_date) {
                    $status = $i->days_late > 0 ? 'Paid late' : 'Paid on time';
                } else {
                    $status = $i->due_date->lt(today()) ? 'Overdue' : 'Unpaid, not yet due';
                }

                fputcsv($out, [
                    $i->invoice_number,
                    $i->client_code,
                    $client?->client_name,
                    $client?->sector,
                    number_format((float) $i->amount, 2, '.', ''),
                    $i->issue_date->toDateString(),
                    $i->due_date->toDateString(),
                    $i->payment_date?->toDateString(),
                    $i->issue_date->diffInDays($i->due_date),
                    $i->payment_date ? $i->days_late : '',
                    $status,
                    $row['tier'] ?? '',
                    $row['confidence'] ?? '',
                    $row['model_used'] ?? '',
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Everything the dashboard, the PDF and the CSV need, in one place. */
    private function buildData(Request $request): array
    {
        [$period, $from, $to, $periodLabel] = $this->period($request);

        $base = compact('period', 'from', 'to', 'periodLabel');

        $kraPin = DB::table('smes')->where('user_id', auth()->id())->value('kra_pin');

        if (! $kraPin) {
            return ['empty' => true] + $base;
        }

        $clients  = Client::where('kra_pin', $kraPin)->orderBy('client_name')->get();
        $codes    = $clients->pluck('client_code');
        $invoices = Invoice::whereIn('client_code', $codes)->get();

        $settled     = $invoices->filter(fn ($i) => $i->payment_date !== null);
        $outstanding = $invoices->filter(fn ($i) => $i->payment_date === null);   // owed today

        $settledInPeriod = $settled->filter(fn ($i) => $i->payment_date->between($from, $to));
        $lateInPeriod    = $settledInPeriod->filter(fn ($i) => $i->days_late > 0);

        [$revenue, $expenses] = $this->budgetTotals($kraPin, $from, $to);

        $assessments = $this->assessEveryClient($clients, $invoices);
        $tierCounts  = ['Low' => 0, 'Medium' => 0, 'High' => 0];

        foreach ($assessments as $row) {
            if ($row['tier']) {
                $tierCounts[$row['tier']]++;
            }
        }

        $months = collect();
        for ($m = $from->copy()->startOfMonth(); $m->lte($to); $m->addMonth()) {
            $months->push($m->copy());
        }
        $months = $months->take(-12);

        $trend = $months->map(function (Carbon $month) use ($settledInPeriod) {
            $inMonth = $settledInPeriod->filter(fn ($i) =>
                $i->payment_date->year === $month->year && $i->payment_date->month === $month->month);

            return [
                'label'   => $month->format('M Y'),
                'short'   => $month->format('M'),
                'value'   => $inMonth->count() ? round($inMonth->avg('days_late'), 1) : null,
                'settled' => $inMonth->count(),
            ];
        })->values();

        return $base + [
            'empty'            => false,
            'receivables'      => $this->short($outstanding->sum('amount')),
            'outstandingCount' => $outstanding->count(),
            'avgDaysLate'      => $lateInPeriod->count() ? round($lateInPeriod->avg('days_late')) : null,
            'latePayments'     => $lateInPeriod->count(),
            'settledCount'     => $settledInPeriod->count(),
            'revenue'          => $this->short($revenue),
            'expenses'         => $this->short($expenses),
            'trend'            => $trend,
            'trendLabels'      => $trend->pluck('short'),
            'trendValues'      => $trend->pluck('value'),
            'tierCounts'       => $tierCounts,
            'clientsCount'     => $clients->count(),
            'assessments'      => $assessments,
            'clientList'       => $clients,
            'invoiceList'      => $invoices,
        ];
    }

    /** Work out the reporting period from the web address. */
    private function period(Request $request): array
    {
        $period = $request->query('period', '6m');
        $to = now()->endOfDay();

        if ($period === 'custom') {
            try {
                $from = Carbon::parse($request->query('from'))->startOfDay();
                $to   = Carbon::parse($request->query('to'))->endOfDay();
            } catch (\Throwable $e) {
                $from = null;
            }

            if ($from && $from->lte($to) && $to->lte(now()->endOfDay())) {
                return ['custom', $from, $to, $from->format('d M Y') . ' to ' . $to->format('d M Y')];
            }

            $period = '6m';
            $to = now()->endOfDay();
        }

        $from = match ($period) {
            'month' => now()->startOfMonth(),
            '3m'    => now()->startOfMonth()->subMonths(2),
            'year'  => now()->startOfYear(),
            default => now()->startOfMonth()->subMonths(5),
        };

        if (! array_key_exists($period, self::PERIODS)) {
            $period = '6m';
        }

        return [$period, $from, $to, self::PERIODS[$period]];
    }

    /**
     * Ask the risk service about every client, using their newest invoice.
     * Cached for ten minutes so the page does not call the service on every refresh.
     */
    private function assessEveryClient($clients, $invoices): array
    {
        $rows = [];

        foreach ($clients as $client) {
            $theirs = $invoices->where('client_code', $client->client_code)
                               ->sortByDesc('issue_date');

            $subject = $theirs->firstWhere('payment_date', null) ?: $theirs->first();

            if (! $subject) {
                continue;
            }

            $settled = $theirs->filter(fn ($i) => $i->payment_date !== null);

            $result = Cache::remember(
                "risk:{$client->client_code}:{$subject->id}:{$subject->updated_at?->timestamp}",
                now()->addMinutes(10),
                fn () => $this->risk->assess($client, $subject)
            );

            $rows[] = [
                'client'         => $client,
                'invoices'       => $theirs->count(),
                'avg_days_late'  => $settled->count() ? round($settled->avg('days_late')) : null,
                'tier'           => $result['tier'] ?? null,
                'confidence'     => $result['confidence'] ?? null,
                'recommendation' => $result['recommendation'] ?? 'Risk service not running',
                'model_used'     => $result['model_used'] ?? null,
                'invoice'        => $subject,
            ];
        }

        $order = ['High' => 0, 'Medium' => 1, 'Low' => 2, null => 3];

        usort($rows, fn ($a, $b) => [$order[$a['tier']], -($a['confidence'] ?? 0)]
                                <=> [$order[$b['tier']], -($b['confidence'] ?? 0)]);

        return $rows;
    }

    /** Revenue and expenses for the budget months inside the period. */
    private function budgetTotals(string $kraPin, Carbon $from, Carbon $to): array
    {
        if (! Schema::hasTable('monthly_budgets')) {
            return [0, 0];
        }

        $cols = Schema::getColumnListing('monthly_budgets');
        $pick = fn (array $names) => collect($names)->first(fn ($n) => in_array($n, $cols, true));

        $revenueCol  = $pick(['revenue_received', 'revenue', 'income', 'total_revenue']);
        $expensesCol = $pick(['expenses_incurred', 'expenses', 'total_expenses', 'expenditure']);
        $monthCol    = $pick(['budget_month', 'month', 'period', 'month_start']);
        $ownerCol    = $pick(['kra_pin', 'sme_kra_pin']);

        if (! $revenueCol || ! $expensesCol) {
            return [0, 0];
        }

        $query = DB::table('monthly_budgets');

        if ($ownerCol) {
            $query->where($ownerCol, $kraPin);
        }

        $rows = $query->get();

        if ($monthCol) {
            $start = $from->copy()->startOfMonth();
            $rows = $rows->filter(function ($row) use ($monthCol, $start, $to) {
                try {
                    return Carbon::parse($row->$monthCol)->between($start, $to);
                } catch (\Throwable $e) {
                    return false;
                }
            });
        }

        return [$rows->sum($revenueCol), $rows->sum($expensesCol)];
    }

    /** 4,820,000 becomes 4.82M so the cards stay readable. */
    private function short($amount): string
    {
        $amount = (float) $amount;

        return match (true) {
            $amount >= 1_000_000 => round($amount / 1_000_000, 2) . 'M',
            $amount >= 1_000     => round($amount / 1_000) . 'K',
            default              => number_format($amount),
        };
    }
}