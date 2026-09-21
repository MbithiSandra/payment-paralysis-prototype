<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

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
            'relationship_start_date' => ['nullable', 'date', 'before_or_equal:today'],
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

        return view('clients.show', compact('client'));
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