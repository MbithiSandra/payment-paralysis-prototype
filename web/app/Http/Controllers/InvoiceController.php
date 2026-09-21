<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    private function sme()
    {
        return auth()->user()->sme;
    }

    private function clientCodes()
    {
        return $this->sme()->clients()->pluck('client_code');
    }

    private function rules(?Invoice $invoice = null): array
    {
        $unique = Rule::unique('invoices')->where(
            fn ($q) => $q->where('client_code', request('client_code'))
        );

        if ($invoice) {
            $unique->ignore($invoice->id);
        }

        return [
            'client_code'    => ['required', Rule::in($this->clientCodes())],
            'invoice_number' => ['required', 'string', 'max:50', $unique],
            'amount'         => ['required', 'numeric', 'min:0.01'],
            'issue_date'     => ['required', 'date'],
            'due_date'       => ['required', 'date', 'after_or_equal:issue_date'],
            'payment_date'   => ['nullable', 'date', 'after_or_equal:issue_date'],
        ];
    }

    public function index()
    {
        $invoices = Invoice::whereIn('client_code', $this->clientCodes())
            ->with('client')
            ->orderByDesc('issue_date')
            ->paginate(20);

        return view('invoices.index', compact('invoices'));
    }

    public function create()
    {
        $clients = $this->sme()->clients()->orderBy('client_name')->get();

        return view('invoices.create', compact('clients'));
    }

    public function store(Request $request)
    {
        Invoice::create($request->validate($this->rules()));

        return redirect()->route('invoices.index')->with('status', 'Invoice recorded.');
    }

    public function edit(Invoice $invoice)
    {
        abort_unless($this->clientCodes()->contains($invoice->client_code), 403);

        $clients = $this->sme()->clients()->orderBy('client_name')->get();

        return view('invoices.edit', compact('invoice', 'clients'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        abort_unless($this->clientCodes()->contains($invoice->client_code), 403);

        $invoice->update($request->validate($this->rules($invoice)));

        return redirect()->route('invoices.index')->with('status', 'Invoice updated.');
    }

    public function destroy(Invoice $invoice)
    {
        abort_unless($this->clientCodes()->contains($invoice->client_code), 403);

        $invoice->delete();

        return redirect()->route('invoices.index')->with('status', 'Invoice removed.');
    }
}