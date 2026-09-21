<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Clients</h2>
            <a href="{{ route('clients.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-xs font-semibold uppercase tracking-widest rounded-md hover:bg-gray-700">
                Add client
            </a>
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8">
        @include('partials.status')

        <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Code</th>
                        <th class="px-4 py-3 text-left font-semibold">Client name</th>
                        <th class="px-4 py-3 text-left font-semibold">Contact</th>
                        <th class="px-4 py-3 text-left font-semibold">Sector</th>
                        <th class="px-4 py-3 text-right font-semibold">Invoices</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($clients as $client)
                        <tr>
                            <td class="px-4 py-3 font-mono">{{ $client->client_code }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('clients.show', $client) }}" class="text-indigo-600 hover:underline">
                                    {{ $client->client_name }}
                                </a>
                            </td>
                            <td class="px-4 py-3">{{ $client->contact_person ?: '-' }}</td>
                            <td class="px-4 py-3">{{ $client->sector ?: '-' }}</td>
                            <td class="px-4 py-3 text-right">{{ $client->invoices_count }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('clients.edit', $client) }}" class="text-indigo-600 hover:underline">Edit</a>
                                <form action="{{ route('clients.destroy', $client) }}" method="POST" class="inline ml-3"
                                      onsubmit="return confirm('Remove this client and all its invoices?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">
                            No clients yet. Add your first one to get started.
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $clients->links() }}</div>
    </div>
</x-app-layout>