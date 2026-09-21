<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Record invoice</h2>
    </x-slot>

    <div class="py-8 max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white shadow-sm sm:rounded-lg p-6">
            @if ($clients->isEmpty())
                <p class="text-gray-600">
                    You need at least one client first.
                    <a href="{{ route('clients.create') }}" class="text-indigo-600 hover:underline">Add a client</a>.
                </p>
            @else
                <form method="POST" action="{{ route('invoices.store') }}">
                    @include('invoices._form', ['submit' => 'Save invoice'])
                </form>
            @endif
        </div>
    </div>
</x-app-layout>