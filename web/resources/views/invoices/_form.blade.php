@csrf
<div class="grid gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <x-input-label for="client_code" value="Client" />
        <select id="client_code" name="client_code" required
                class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            <option value="">Select a client</option>
            @foreach ($clients as $c)
                <option value="{{ $c->client_code }}"
                    @selected(old('client_code', $invoice->client_code ?? '') === $c->client_code)>
                    {{ $c->client_name }} ({{ $c->client_code }})
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('client_code')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="invoice_number" value="Invoice number" />
        <x-text-input id="invoice_number" name="invoice_number" type="text" class="block mt-1 w-full"
                      :value="old('invoice_number', $invoice->invoice_number ?? '')" required />
        <x-input-error :messages="$errors->get('invoice_number')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="amount" value="Amount (KES)" />
        <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01" class="block mt-1 w-full"
                      :value="old('amount', $invoice->amount ?? '')" required />
        <x-input-error :messages="$errors->get('amount')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="issue_date" value="Issue date" />
        <x-text-input id="issue_date" name="issue_date" type="date" class="block mt-1 w-full"
                      :value="old('issue_date', isset($invoice) ? $invoice->issue_date->format('Y-m-d') : '')" required />
        <x-input-error :messages="$errors->get('issue_date')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="due_date" value="Due date" />
        <x-text-input id="due_date" name="due_date" type="date" class="block mt-1 w-full"
                      :value="old('due_date', isset($invoice) ? $invoice->due_date->format('Y-m-d') : '')" required />
        <x-input-error :messages="$errors->get('due_date')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="payment_date" value="Payment date (leave blank if not yet paid)" />
        <x-text-input id="payment_date" name="payment_date" type="date" class="block mt-1 w-full"
                      :value="old('payment_date', isset($invoice) && $invoice->payment_date ? $invoice->payment_date->format('Y-m-d') : '')" />
        <x-input-error :messages="$errors->get('payment_date')" class="mt-2" />
        <p class="mt-1 text-xs text-gray-500">
            This is the field the risk model learns from. Fill it in as soon as the client pays.
        </p>
    </div>
</div>

<div class="flex items-center justify-end gap-4 mt-6">
    <a href="{{ route('invoices.index') }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
    <x-primary-button>{{ $submit }}</x-primary-button>
</div>