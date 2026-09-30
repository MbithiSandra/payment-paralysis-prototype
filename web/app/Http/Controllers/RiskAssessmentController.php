<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\RiskAssessmentService;
use Illuminate\Support\Facades\DB;

class RiskAssessmentController extends Controller
{
    public function __construct(private RiskAssessmentService $risk)
    {
    }

    public function show(Invoice $invoice)
    {
        $client = $invoice->client;

        // an owner may only assess invoices of their own clients
        $ownerPin = DB::table('smes')->where('user_id', auth()->id())->value('kra_pin');
        abort_unless($ownerPin && $client->kra_pin === $ownerPin, 403);

        $assessment = $this->risk->assess($client, $invoice);

        if ($assessment === null) {
            return back()->with('error',
                'The risk assessment service is not responding. Start it and try again.');
        }

        return view('risk.show', compact('invoice', 'client', 'assessment'));
    }
}