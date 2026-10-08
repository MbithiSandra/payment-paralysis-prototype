<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Services\RiskAssessmentService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function __construct(private RiskAssessmentService $risk)
    {
    }

    public function index()
    {
       $kraPin = DB::table('smes')->where('user_id', auth()->id())->value('kra_pin');

        if (! $kraPin) {
            return view('dashboard', ['empty' => true]);
        }

        $clients  = Client::where('kra_pin', $kraPin)->orderBy('client_name')->get();
        $codes    = $clients->pluck('client_code');
        $invoices = Invoice::whereIn('client_code', $codes)->get();

        $settled     = $invoices->filter(fn ($i) => $i->payment_date !== null);
        $outstanding = $invoices->filter(fn ($i) => $i->payment_date === null);
        $lateSettled = $settled->filter(fn ($i) => $i->days_late > 0);

        [$revenue, $expenses] = $this->budgetTotals($kraPin);

        $assessments = $this->assessEveryClient($clients, $invoices);
        $tierCounts  = ['Low' => 0, 'Medium' => 0, 'High' => 0];

        foreach ($assessments as $row) {
            if ($row['tier']) {
                $tierCounts[$row['tier']]++;
            }
        }

        $months = collect(range(5, 0))->map(fn ($back) => Carbon::now()->startOfMonth()->subMonths($back));

        $trend = $months->map(function (Carbon $month) use ($settled) {
            $inMonth = $settled->filter(fn ($i) =>
                $i->payment_date->year === $month->year && $i->payment_date->month === $month->month);

            return [
                'label' => $month->format('M'),
                'value' => $inMonth->count() ? round($inMonth->avg('days_late'), 1) : null,
            ];
        });

        return view('dashboard', [
            'empty'            => false,
            'receivables'      => $this->short($outstanding->sum('amount')),
            'outstandingCount' => $outstanding->count(),
            'avgDaysLate'      => $lateSettled->count() ? round($lateSettled->avg('days_late')) : null,
            'latePayments'     => $lateSettled->count(),
            'settledCount'     => $settled->count(),
            'revenue'          => $this->short($revenue),
            'expenses'         => $this->short($expenses),
            'trendLabels'      => $trend->pluck('label'),
            'trendValues'      => $trend->pluck('value'),
            'tierCounts'       => $tierCounts,
            'clientsCount'     => $clients->count(),
            'assessments'      => $assessments,
        ]);
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
                continue;                       // a client with no invoices yet
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

    /**
     * Revenue and expenses from the last six monthly budget entries.
     * Works out the column names so it fits whatever the budget table calls them.
     */
    private function budgetTotals(string $kraPin): array
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
            return [0, 0, 0];
        }

        $query = DB::table('monthly_budgets');

        if ($ownerCol) {
            $query->where($ownerCol, $kraPin);
        }

        if ($monthCol) {
            $query->orderByDesc($monthCol);
        }

        $rows = $query->limit(6)->get();

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