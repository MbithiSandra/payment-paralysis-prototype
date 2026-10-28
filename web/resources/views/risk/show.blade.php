<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="pg-display text-3xl">Risk assessment</h1>
                <p class="text-sm text-[color:var(--pg-muted)] mt-1">
                    {{ $client->client_name }} &middot;
                    @if ($invoice)
                        invoice {{ $invoice->invoice_number }} of
                        KES {{ number_format($invoice->amount, 2) }}, due {{ $invoice->due_date->format('d M Y') }}
                    @else
                        proposed credit of KES {{ number_format($proposal['amount'], 2) }}
                        on {{ $proposal['terms_days'] }} day terms, due {{ $proposal['due_date']->format('d M Y') }}
                    @endif
                </p>
            </div>
            @if ($invoice)
                <a href="{{ route('invoices.index') }}" class="pg-chip">Back to invoices</a>
            @else
                <a href="{{ route('assess.create', ['client' => $client->client_code]) }}" class="pg-chip">Assess again</a>
            @endif
        </div>
    </x-slot>

    <div class="pb-14 pt-8 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

        @if (session('status'))
            <div class="pg-tile pg-tile--sage text-sm">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="pg-tile pg-tile--clay text-sm">{{ session('error') }}</div>
        @endif

        {{-- Verdict --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <div class="pg-card-dark lg:col-span-2 p-7">
                <p class="pg-label" style="color:#8FB3A8">Predicted risk tier</p>
                <div class="flex items-baseline gap-4 mt-3">
                    <span class="pg-display text-5xl" style="color:#F2F7F4">{{ $assessment['tier'] }}</span>
                    <span class="text-sm" style="color:#9DBCB1">
                        {{ $assessment['confidence'] }}% chance of settling late
                    </span>
                </div>
                <div class="mt-7 pt-5 grid grid-cols-2 gap-4" style="border-top:1px solid #2C5048">
                    <div>
                        <p class="pg-label" style="color:#8FB3A8">Model used</p>
                        <p class="mt-1" style="color:#F2F7F4">{{ ucfirst($assessment['model_used']) }}</p>
                    </div>
                    <div>
                        <p class="pg-label" style="color:#8FB3A8">Invoices settled</p>
                        <p class="mt-1" style="color:#F2F7F4">{{ $assessment['settled_invoices'] }}</p>
                    </div>
                </div>
            </div>

            <div class="pg-card p-7">
                <p class="pg-label">Recommended action</p>
                <p class="mt-3 text-[15px] leading-relaxed">{{ $assessment['recommendation'] }}</p>
                @if ($assessment['medium_floor_applied'])
                    <p class="text-xs text-[color:var(--pg-muted)] mt-4">
                        Raised to Medium because this client has no settled invoices yet.
                    </p>
                @endif
            </div>
        </div>

        {{-- Why --}}
        <div class="pg-card overflow-hidden">
            <div class="px-7 pt-6 pb-2">
                <h2 class="pg-display text-xl">Why this tier</h2>
                <p class="text-sm text-[color:var(--pg-muted)] mt-1">SHAP feature contributions</p>
            </div>
            <table class="min-w-full pg-table">
                <thead><tr><th>Factor</th><th class="text-right">Value</th><th>Effect</th></tr></thead>
                <tbody>
                @foreach ($assessment['explanation'] as $reason)
                    <tr>
                        <td>{{ $reason['label'] }}</td>
                        <td class="text-right">{{ round($reason['value'], 2) }}</td>
                        <td>
                            <span class="pg-pill {{ $reason['impact'] > 0 ? 'pg-pill--high' : 'pg-pill--low' }}">
                                {{ $reason['direction'] }}
                            </span>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <p class="text-xs text-[color:var(--pg-muted)] px-7 py-5">
                These factors are linked to late payment in the training data. They do not prove cause.
            </p>
        </div>

        {{-- Try different terms --}}
        @if (! empty($alternatives))
            <div class="pg-card overflow-hidden">
                <div class="px-7 pt-6 pb-2">
                    <h2 class="pg-display text-xl">Try different terms</h2>
                    <p class="text-sm text-[color:var(--pg-muted)] mt-1">
                        What the risk would be if you gave this client new credit today on other terms
                    </p>
                </div>

                @if ($assessment['model_used'] === 'profile')
                    <div class="pg-tile pg-tile--mist text-sm mx-7 mb-6 mt-3">
                        This client has fewer than three settled invoices, so the system judges them on their business
                        profile only. Changing the amount or the days does not change the risk yet. Start with a small
                        order and let their payment record build up.
                    </div>
                @else
                    <table class="min-w-full pg-table">
                        <thead>
                            <tr>
                                <th>Option</th>
                                <th class="text-right">Amount (KES)</th>
                                <th class="text-right">Days to pay</th>
                                <th>Tier</th>
                                <th class="text-right">Chance late</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($alternatives as $alt)
                            <tr @if ($alt['best']) style="background:#EEF4EF" @endif>
                                <td class="font-semibold">
                                    {{ $alt['label'] }}
                                    @if ($alt['best'])
                                        <span class="pg-pill pg-pill--low ml-2">lowest risk</span>
                                    @endif
                                </td>
                                <td class="text-right">{{ number_format($alt['amount'], 2) }}</td>
                                <td class="text-right">{{ $alt['terms'] }}</td>
                                <td><span class="pg-pill pg-pill--{{ strtolower($alt['tier']) }}">{{ $alt['tier'] }}</span></td>
                                <td class="text-right font-semibold">{{ $alt['confidence'] }}%</td>
                                <td class="text-right">
                                    <a href="{{ route('assess.result', ['client' => $client->client_code, 'amount' => $alt['amount'], 'terms_days' => $alt['terms']]) }}"
                                       class="hover:underline">Open</a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <p class="text-xs text-[color:var(--pg-muted)] px-7 py-5">
                        Use this to agree terms the client can keep. A shorter window or a smaller first order often brings the risk down.
                    </p>
                @endif
            </div>
        @endif

        {{-- Record the decision --}}
        <div class="pg-card p-7">
            <h2 class="pg-display text-xl">Record the decision you took</h2>
            <p class="text-sm text-[color:var(--pg-muted)] mt-1">
                The system advises. You decide. Give a reason if you go a different way.
            </p>

            <form method="POST"
                  action="{{ $invoice ? route('decisions.store', $invoice) : route('assess.decision', $client) }}"
                  class="mt-6 space-y-5">
                @csrf
                <input type="hidden" name="recommended_tier"   value="{{ $assessment['tier'] }}">
                <input type="hidden" name="probability_late"   value="{{ $assessment['probability_late'] }}">
                <input type="hidden" name="model_used"         value="{{ $assessment['model_used'] }}">
                <input type="hidden" name="recommended_action" value="{{ $assessment['recommendation'] }}">

                <div class="space-y-2">
                    @foreach (\App\Models\CreditDecision::CHOICES as $value => $label)
                        <label class="flex items-center gap-3 px-4 py-3 rounded-xl cursor-pointer"
                               style="border:1px solid var(--pg-line)">
                            <input type="radio" name="decision" value="{{ $value }}"
                                   @checked(old('decision') === $value
                                            || (! old('decision') && \App\Models\CreditDecision::expectedFor($assessment['tier']) === $value))
                                   style="accent-color: var(--pg-accent)">
                            <span class="text-sm">{{ $label }}</span>
                            @if (\App\Models\CreditDecision::expectedFor($assessment['tier']) === $value)
                                <span class="pg-pill pg-pill--low ml-auto">recommended</span>
                            @endif
                        </label>
                    @endforeach
                </div>

                <div>
                    <label class="pg-label">Reason, if you differ from the recommendation</label>
                    <textarea name="override_reason" rows="3"
                              class="mt-2 w-full rounded-xl text-sm"
                              style="border:1px solid var(--pg-line); background:#fff; padding:12px 14px"
                              placeholder="For example, long standing client who has guaranteed payment in person">{{ old('override_reason') }}</textarea>
                    @error('override_reason')
                        <p class="text-xs mt-2" style="color:var(--pg-high)">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="pg-btn">Save decision</button>
            </form>
        </div>
    </div>
</x-app-layout>