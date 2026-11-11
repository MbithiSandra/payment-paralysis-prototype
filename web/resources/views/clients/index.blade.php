<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="pg-display text-3xl">Clients</h1>
                <p class="text-sm text-[color:var(--pg-muted)] mt-1">The businesses you sell to on credit</p>
            </div>
            <a href="{{ route('clients.create') }}" class="pg-btn">Add client</a>
        </div>
    </x-slot>

    <div class="pb-14 pt-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
        @include('partials.status')

        {{-- Search and filter --}}
        <form method="GET" action="{{ route('clients.index') }}" class="pg-card p-5 flex flex-wrap items-center gap-3">
            <input type="text" name="q" value="{{ $search ?? '' }}" placeholder="Search by name or client code"
                   class="flex-1 min-w-[220px] rounded-xl text-sm"
                   style="border:1px solid var(--pg-line); background:#fff; padding:10px 14px">
            <select name="sector" class="rounded-xl text-sm"
                    style="border:1px solid var(--pg-line); background:#fff; padding:10px 14px">
                <option value="">All sectors</option>
                @foreach (\App\Models\Client::SECTORS as $s)
                    <option value="{{ $s }}" @selected(($sector ?? '') === $s)>{{ $s }}</option>
                @endforeach
            </select>
            <button type="submit" class="pg-btn">Search</button>
            @if (($search ?? '') !== '' || ($sector ?? '') !== '')
                <a href="{{ route('clients.index') }}" class="pg-chip">Clear</a>
            @endif
            <span class="text-xs text-[color:var(--pg-muted)] ml-auto">
                {{ $clients->total() }} {{ Str::plural('client', $clients->total()) }}
            </span>
        </form>

        <div class="pg-card overflow-hidden">
            <table class="min-w-full pg-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Client name</th>
                        <th>Contact</th>
                        <th>Sector</th>
                        <th class="text-right">Invoices</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($clients as $client)
                        <tr>
                            <td class="font-mono text-xs">{{ $client->client_code }}</td>
                            <td class="font-semibold">
                                <a href="{{ route('clients.show', $client) }}" class="hover:underline">
                                    {{ $client->client_name }}
                                </a>
                            </td>
                            <td class="text-[color:var(--pg-muted)]">{{ $client->contact_person ?: '-' }}</td>
                            <td class="text-[color:var(--pg-muted)]">{{ $client->sector ?: '-' }}</td>
                            <td class="text-right">{{ $client->invoices_count }}</td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('assess.create', ['client' => $client->client_code]) }}"
                                   class="hover:underline font-semibold" style="color:var(--pg-accent)">Assess</a>
                                <a href="{{ route('clients.edit', $client) }}" class="hover:underline ml-3">Edit</a>
                                <form action="{{ route('clients.destroy', $client) }}" method="POST" class="inline ml-3"
                                      onsubmit="return confirm('Remove this client and all its invoices?')">
                                    @csrf @method('DELETE')
                                    <button class="hover:underline" style="color:var(--pg-high)">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-[color:var(--pg-muted)] py-12">
                            @if (($search ?? '') !== '' || ($sector ?? '') !== '')
                                No clients match your search.
                            @else
                                No clients yet. Add your first one to get started.
                            @endif
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $clients->links() }}</div>
    </div>
</x-app-layout>