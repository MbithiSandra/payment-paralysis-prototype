<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CreditDecision;
use App\Models\Invoice;
use App\Services\RiskAssessmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RiskAssessmentController extends Controller
{
    public function __construct(private RiskAssessmentService $risk)
    {
    }

    private function ownerPin(): ?string
    {
        return DB::table('smes')->where('user_id', auth()->id())->value('kra_pin');
    }

    /** Assess a saved invoice (from the invoices list or dashboard). */
    public function show(Invoice $invoice)
    {
        $client = $invoice->client;

        $ownerPin = $this->ownerPin();
        abort_unless($ownerPin && $client->kra_pin === $ownerPin, 403);

        $assessment = $this->risk->assess($client, $invoice);

        if ($assessment === null) {
            return back()->with('error',
                'The risk assessment service is not responding. Start it and try again.');
        }

        $proposal = null;
        $alternatives = $this->alternatives($client, (float) $invoice->amount,
            (int) $invoice->issue_date->diffInDays($invoice->due_date));

        return view('risk.show', compact('invoice', 'client', 'assessment', 'proposal', 'alternatives'));
    }

    /** FR05: the form. */
    public function create(Request $request)
    {
        $clients = Client::where('kra_pin', $this->ownerPin())->orderBy('client_name')->get();

        return view('risk.create', [
            'clients'  => $clients,
            'selected' => $request->query('client'),
        ]);
    }

    /** FR05: run the assessment on the proposed terms. */
    public function proposal(Request $request)
    {
        $data = $request->validate([
            'client'     => 'required|string',
            'amount'     => 'required|numeric|min:1|max:1000000000',
            'terms_days' => 'required|integer|min:0|max:365',
        ]);

        $client = Client::where('client_code', $data['client'])
            ->where('kra_pin', $this->ownerPin())
            ->firstOrFail();

        $issue = now()->startOfDay();

        $assessment = $this->risk->assessTerms(
            $client, (float) $data['amount'], $issue, (int) $data['terms_days']);

        if ($assessment === null) {
            return redirect()->route('assess.create', ['client' => $client->client_code])
                ->withInput()
                ->with('error', 'The risk assessment service is not responding. Start it and try again.');
        }

        $proposal = [
            'amount'     => (float) $data['amount'],
            'terms_days' => (int) $data['terms_days'],
            'due_date'   => $issue->copy()->addDays((int) $data['terms_days']),
        ];
        $invoice = null;
        $alternatives = $this->alternatives($client, (float) $data['amount'], (int) $data['terms_days']);

        return view('risk.show', compact('invoice', 'client', 'assessment', 'proposal', 'alternatives'));
    }

    /** FR09 for a proposal: record the decision without an invoice. */
    public function storeDecision(Request $request, Client $client)
    {
        abort_unless($client->kra_pin === $this->ownerPin(), 403);

        $data = $request->validate([
            'decision'           => 'required|in:' . implode(',', array_keys(CreditDecision::CHOICES)),
            'recommended_tier'   => 'required|in:Low,Medium,High',
            'probability_late'   => 'required|numeric',
            'model_used'         => 'required|string',
            'recommended_action' => 'required|string',
            'override_reason'    => 'nullable|string|max:500',
        ]);

        $followed = $data['decision'] === CreditDecision::expectedFor($data['recommended_tier']);

        if (! $followed && blank($data['override_reason'] ?? null)) {
            return back()->withInput()
                ->withErrors(['override_reason' => 'Please give a reason when you differ from the recommendation.']);
        }

        (new CreditDecision)->forceFill([
            'user_id'                 => auth()->id(),
            'invoice_id'              => null,
            'client_code'             => $client->client_code,
            'recommended_tier'        => $data['recommended_tier'],
            'probability_late'        => $data['probability_late'],
            'model_used'              => $data['model_used'],
            'recommended_action'      => $data['recommended_action'],
            'decision'                => $data['decision'],
            'override_reason'         => $data['override_reason'] ?? null,
            'followed_recommendation' => $followed,
        ])->save();

        return redirect()->route('decisions.index')->with('status', 'Decision recorded.');
    }

    /** Prescriptive: the same client on four other sets of terms, as if credit were given today. */
    private function alternatives(Client $client, float $amount, int $terms): array
    {
        $options = [
            ['label' => 'Shorter terms',   'amount' => $amount,               'terms' => 14],
            ['label' => 'Standard terms',  'amount' => $amount,               'terms' => 30],
            ['label' => 'Longer terms',    'amount' => $amount,               'terms' => 60],
            ['label' => 'Half the amount', 'amount' => round($amount / 2, 2), 'terms' => $terms],
        ];

        $issue = now()->startOfDay();
        $rows  = [];

        foreach ($options as $option) {
            $result = $this->risk->assessTerms($client, $option['amount'], $issue, $option['terms']);
            if ($result) {
                $rows[] = $option + ['tier' => $result['tier'], 'confidence' => $result['confidence']];
            }
        }

        if ($rows) {
            $best = min(array_column($rows, 'confidence'));
            foreach ($rows as $i => $row) {
                $rows[$i]['best'] = $row['confidence'] == $best;
            }
        }

        return $rows;
    }
}