<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use App\Services\RiskAssessmentService;
use Illuminate\Support\Facades\Cache;

class ClientController extends Controller
{
    private function sme()
    {
        return auth()->user()->sme;
    }

    private function nextClientCode(): string
    {
        $last = $this->sme()->clients()->orderByDesc('client_code')->first();
        $n    = $last ? ((int) substr($last->client_code, 3)) + 1 : 1;

        do {
            $code = 'CL-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
            $n++;
        } while (Client::where('client_code', $code)->exists());

        return $code;
    }

    private function rules(): array
    {
        return [
            'client_name'             => ['required', 'string', 'max:255'],
            'client_kra_pin'          => ['nullable', 'string', 'max:20'],
            'contact_person'          => ['nullable', 'string', 'max:255'],
            'phone'                   => ['nullable', 'string', 'max:20'],
            'email'                   => ['nullable', 'email', 'max:255'],
            'sector'                  => ['nullable', 'string', 'max:255'], 
            'employees'               => ['nullable', 'integer', 'min:0'],
            'years_in_operation' => ['nullable', 'numeric', 'min:0', 'max:99'],
            'employees'          => ['nullable', 'integer', 'min:1', 'max:250'],
        ];
    }

    public function index()
    {
        $clients = $this->sme()->clients()
            ->withCount('invoices')
            ->orderBy('client_name')
            ->paginate(15);

        return view('clients.index', compact('clients'));
    }

    public function create()
    {
        return view('clients.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $data['client_code'] = $this->nextClientCode();
        $data['kra_pin']     = $this->sme()->kra_pin;

        Client::create($data);

        return redirect()->route('clients.index')
            ->with('status', 'Client ' . $data['client_code'] . ' was added.');
    }

    public function show(Client $client)
    {
        abort_unless($client->kra_pin === $this->sme()->kra_pin, 403);

        $client->load(['invoices' => fn ($q) => $q->orderByDesc('issue_date')]);

        $riskHistory = $this->riskHistory($client);

        return view('clients.show', compact('client', 'riskHistory'));
    }

    /** Chance late at each of the last twelve invoices, using only what was known on that day. */
    private function riskHistory(Client $client): array
    {
        $risk = app(RiskAssessmentService::class);

        return $client->invoices->sortBy('issue_date')->take(-12)->map(function ($inv) use ($client, $risk) {
            $result = Cache::remember(
                "risk:{$client->client_code}:{$inv->id}:{$inv->updated_at?->timestamp}",
                now()->addMinutes(10),
                fn () => $risk->assess($client, $inv)
            );

            return [
                'label'  => $inv->issue_date->format('d M y'),
                'number' => $inv->invoice_number,
                'chance' => $result['confidence'] ?? null,
                'tier'   => $result['tier'] ?? null,
                'model'  => $result['model_used'] ?? null,
            ];
        })->values()->all();
    }

    public function edit(Client $client)
    {
        abort_unless($client->kra_pin === $this->sme()->kra_pin, 403);

        return view('clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        abort_unless($client->kra_pin === $this->sme()->kra_pin, 403);

        $client->update($request->validate($this->rules()));

        return redirect()->route('clients.index')->with('status', 'Client updated.');
    }

    public function destroy(Client $client)
    {
        abort_unless($client->kra_pin === $this->sme()->kra_pin, 403);

        $client->delete();

        return redirect()->route('clients.index')->with('status', 'Client removed.');
    }
}