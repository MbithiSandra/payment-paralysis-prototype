<?php

namespace App\Http\Controllers;

use App\Models\CreditDecision;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreditDecisionController extends Controller
{
    public function index()
    {
        $kraPin = DB::table('smes')->where('user_id', auth()->id())->value('kra_pin');

        $decisions = CreditDecision::with(['client', 'invoice'])
            ->whereHas('client', fn ($q) => $q->where('kra_pin', $kraPin))
            ->latest()
            ->paginate(20);

        $total    = CreditDecision::whereHas('client', fn ($q) => $q->where('kra_pin', $kraPin))->count();
        $followed = CreditDecision::whereHas('client', fn ($q) => $q->where('kra_pin', $kraPin))
                        ->where('followed_recommendation', true)->count();

        return view('decisions.index', [
            'decisions'  => $decisions,
            'total'      => $total,
            'followed'   => $followed,
            'agreement'  => $total ? round($followed / $total * 100) : null,
        ]);
    }

    public function store(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'decision'          => ['required', 'in:' . implode(',', array_keys(CreditDecision::CHOICES))],
            'recommended_tier'  => ['required', 'in:Low,Medium,High'],
            'probability_late'  => ['required', 'numeric'],
            'model_used'        => ['required', 'string', 'max:20'],
            'recommended_action'=> ['required', 'string'],
            'override_reason'   => ['nullable', 'string', 'max:500'],
        ]);

        $followed = $data['decision'] === CreditDecision::expectedFor($data['recommended_tier']);

        if (! $followed && blank($data['override_reason'])) {
            return back()->withInput()->withErrors([
                'override_reason' => 'Give a reason when the decision differs from the recommendation.',
            ]);
        }

        CreditDecision::create($data + [
            'user_id'                 => auth()->id(),
            'invoice_id'              => $invoice->id,
            'client_code'             => $invoice->client_code,
            'followed_recommendation' => $followed,
        ]);

        return back()->with('status', 'Decision recorded.');
    }
}