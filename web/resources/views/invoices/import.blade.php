<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="pg-display text-3xl">Import invoices</h1>
                <p class="text-sm text-[color:var(--pg-muted)] mt-1">
                    Add many invoices at once from a spreadsheet. New clients are created for you.
                </p>
            </div>
            <a href="{{ route('invoices.index') }}" class="pg-chip">Back to invoices</a>
        </div>
    </x-slot>

    <div class="pb-14 pt-8 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

        @if (session('error'))
            <div class="pg-tile pg-tile--clay text-sm">{{ session('error') }}</div>
        @endif

        @if (session('import_errors'))
            <div class="pg-tile pg-tile--clay text-sm">
                <p class="font-semibold">
                    Nothing was saved. {{ session('import_error_total') }}
                    {{ Str::plural('row has', session('import_error_total')) === 'row has' ? 'row has' : 'rows have' }}
                    a problem. Fix them in your file and upload it again.
                </p>
                <ul class="mt-3 space-y-1">
                    @foreach (session('import_errors') as $line)
                        <li>{{ $line }}</li>
                    @endforeach
                </ul>
                @if (session('import_error_total') > 50)
                    <p class="mt-3 text-xs">Showing the first 50.</p>
                @endif
            </div>
        @endif

        <div class="pg-card p-7">
            <h2 class="pg-display text-xl">1. Get the template</h2>
            <p class="text-sm text-[color:var(--pg-muted)] mt-1">
                Open it in Excel or Google Sheets, replace the example rows with your own, then save as CSV.
            </p>
            <a href="{{ route('invoices.import.template') }}" class="pg-chip inline-block mt-4">Download template</a>

            <table class="min-w-full pg-table mt-6">
                <thead><tr><th>Column</th><th>What to put</th><th>Needed</th></tr></thead>
                <tbody>
                    <tr><td class="font-mono text-xs">client_name</td><td>Business name, spelled the same every time</td><td>Yes</td></tr>
                    <tr><td class="font-mono text-xs">sector</td><td>Same names as the client form, for example Construction or Wholesale</td><td>For new clients</td></tr>
                    <tr><td class="font-mono text-xs">invoice_number</td><td>Your invoice number</td><td>Yes</td></tr>
                    <tr><td class="font-mono text-xs">amount</td><td>Amount in KES, for example 85000</td><td>Yes</td></tr>
                    <tr><td class="font-mono text-xs">issue_date</td><td>2026-06-02 or 02/06/2026</td><td>Yes</td></tr>
                    <tr><td class="font-mono text-xs">due_date</td><td>Same date format</td><td>Yes</td></tr>
                    <tr><td class="font-mono text-xs">payment_date</td><td>Leave empty if not yet paid</td><td>No</td></tr>
                </tbody>
            </table>
        </div>

        <form method="POST" action="{{ route('invoices.import.store') }}" enctype="multipart/form-data"
              class="pg-card p-7">
            @csrf
            <h2 class="pg-display text-xl">2. Upload your file</h2>
            <p class="text-sm text-[color:var(--pg-muted)] mt-1">
                Every row is checked first. If any row has a problem, nothing is saved and you will see which rows to fix.
            </p>

            <input type="file" name="file" accept=".csv,text/csv" required
                   class="mt-5 block w-full text-sm rounded-xl"
                   style="border:1px solid var(--pg-line); background:#fff; padding:10px 12px">
            @error('file')
                <p class="text-xs mt-2" style="color:var(--pg-high)">{{ $message }}</p>
            @enderror

            <button type="submit" class="pg-btn mt-5">Import</button>
        </form>
    </div>
</x-app-layout>