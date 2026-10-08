<x-app-layout>
    <x-slot name="header">
        <h1 class="pg-display text-3xl">Recorded decisions</h1>
        <p class="text-sm text-[color:var(--pg-muted)] mt-1">
            Every credit decision taken, against what the system recommended
        </p>
    </x-slot>

    <div class="pb-14 pt-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

        <div class="grid grid-cols-2 lg:grid-cols-3 gap-5">
            <div class="pg-tile pg-tile--sand">
                <p class="pg-label">Decisions recorded</p>
                <p class="pg-figure mt-2">{{ $total }}</p>
            </div>
            <div class="pg-tile pg-tile--sage">
                <p class="pg-label">Followed the recommendation</p>
                <p class="pg-figure mt-2">{{ $followed }}</p>
            </div>
            <div class="pg-tile pg-tile--mist">
                <p class="pg-label">Agreement rate</p>
                <p class="pg-figure mt-2">{{ $agreement !== null ? $agreement . '%' : '-' }}</p>
            </div>
        </div>

        <div class="pg-card overflow-hidden">
            <table class="min-w-full pg-table">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Client</th>
                        <th>Recommended</th>
                        <th>Decision taken</th>
                        <th>Reason given</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($decisions as $d)
                    <tr>
                        <td class="text-[color:var(--pg-muted)]">{{ $d->created_at->format('d M Y') }}</td>
                        <td class="font-semibold">{{ $d->client->client_name ?? $d->client_code }}</td>
                        <td>
                            <span class="pg-pill pg-pill--{{ strtolower($d->recommended_tier) }}">
                                {{ $d->recommended_tier }}
                            </span>
                        </td>
                        <td>
                            {{ \App\Models\CreditDecision::CHOICES[$d->decision] }}
                            @unless ($d->followed_recommendation)
                                <span class="pg-pill pg-pill--medium ml-2">overridden</span>
                            @endunless
                        </td>
                        <td class="text-[color:var(--pg-muted)]">{{ $d->override_reason ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-[color:var(--pg-muted)] py-12">
                        No decisions recorded yet. Assess an invoice and record what you decided.
                    </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $decisions->links() }}</div>
    </div>
</x-app-layout>