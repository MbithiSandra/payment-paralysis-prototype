<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Risk assessment') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('error'))
                <div class="bg-red-100 text-red-800 border border-red-300 rounded p-4">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 space-y-6 text-gray-900">

                <p class="text-gray-600">
                    {{ $client->client_name }} &middot; invoice of
                    KES {{ number_format($invoice->amount, 2) }}, due {{ $invoice->due_date->format('d M Y') }}
                </p>

                @php
                    $tierColour = [
                        'Low' => 'bg-green-100 text-green-800 border-green-300',
                        'Medium' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
                        'High' => 'bg-red-100 text-red-800 border-red-300',
                    ][$assessment['tier']];
                @endphp

                <div class="grid grid-cols-3 gap-4">
                    <div class="border rounded p-4 {{ $tierColour }}">
                        <div class="text-sm uppercase">Risk tier</div>
                        <div class="text-3xl font-bold">{{ $assessment['tier'] }}</div>
                    </div>
                    <div class="border rounded p-4">
                        <div class="text-sm uppercase text-gray-500">Chance of paying late</div>
                        <div class="text-3xl font-bold">{{ $assessment['confidence'] }}%</div>
                    </div>
                    <div class="border rounded p-4">
                        <div class="text-sm uppercase text-gray-500">Settled invoices</div>
                        <div class="text-3xl font-bold">{{ $assessment['settled_invoices'] }}</div>
                    </div>
                </div>

                <div class="border rounded p-4 bg-gray-50">
                    <h3 class="font-semibold mb-1">Recommended action</h3>
                    <p>{{ $assessment['recommendation'] }}</p>
                </div>

                <div>
                    <h3 class="font-semibold mb-2">Why this rating</h3>
                    <table class="w-full text-sm border">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="text-left p-2">Factor</th>
                                <th class="text-right p-2">Value</th>
                                <th class="text-left p-2">Effect</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($assessment['explanation'] as $reason)
                            <tr class="border-t">
                                <td class="p-2">{{ $reason['label'] }}</td>
                                <td class="p-2 text-right">{{ round($reason['value'], 2) }}</td>
                                <td class="p-2 {{ $reason['impact'] > 0 ? 'text-red-700' : 'text-green-700' }}">
                                    {{ $reason['direction'] }}
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <p class="text-xs text-gray-500 mt-2">
                        These factors are linked to late payment in the training data. They do not prove cause.
                    </p>
                </div>

                <p class="text-xs text-gray-500">
                    Assessed with the {{ $assessment['model_used'] }} model.
                    @if ($assessment['medium_floor_applied'])
                        Raised to Medium because this client has no settled invoices yet.
                    @endif
                </p>

                <a href="{{ route('invoices.index') }}" class="text-blue-600 underline">Back to invoices</a>
            </div>
        </div>
    </div>
</x-app-layout>