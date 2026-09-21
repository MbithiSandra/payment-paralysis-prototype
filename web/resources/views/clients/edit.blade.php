<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit {{ $client->client_name }}
            <span class="font-mono text-gray-500">({{ $client->client_code }})</span>
        </h2>
    </x-slot>

    <div class="py-8 max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white shadow-sm sm:rounded-lg p-6">
            <form method="POST" action="{{ route('clients.update', $client) }}">
                @method('PUT')
                @include('clients._form', ['submit' => 'Update client'])
            </form>
        </div>
    </div>
</x-app-layout>