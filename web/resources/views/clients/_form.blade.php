@csrf
<div class="grid gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <x-input-label for="client_name" value="Client name" />
        <x-text-input id="client_name" name="client_name" type="text" class="block mt-1 w-full"
                      :value="old('client_name', $client->client_name ?? '')" required />
        <x-input-error :messages="$errors->get('client_name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="client_kra_pin" value="Client KRA PIN (optional)" />
        <x-text-input id="client_kra_pin" name="client_kra_pin" type="text" class="block mt-1 w-full"
                      :value="old('client_kra_pin', $client->client_kra_pin ?? '')" />
        <x-input-error :messages="$errors->get('client_kra_pin')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="sector" value="Sector" />
        <x-text-input id="sector" name="sector" type="text" class="block mt-1 w-full"
                      :value="old('sector', $client->sector ?? '')" placeholder="e.g. Retail" />
        <x-input-error :messages="$errors->get('sector')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="contact_person" value="Contact person" />
        <x-text-input id="contact_person" name="contact_person" type="text" class="block mt-1 w-full"
                      :value="old('contact_person', $client->contact_person ?? '')" />
        <x-input-error :messages="$errors->get('contact_person')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="phone" value="Phone" />
        <x-text-input id="phone" name="phone" type="text" class="block mt-1 w-full"
                      :value="old('phone', $client->phone ?? '')" placeholder="07XX XXX XXX" />
        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="email" value="Email" />
        <x-text-input id="email" name="email" type="email" class="block mt-1 w-full"
                      :value="old('email', $client->email ?? '')" />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="relationship_start_date" value="Client since" />
        <x-text-input id="relationship_start_date" name="relationship_start_date" type="date" class="block mt-1 w-full"
                      :value="old('relationship_start_date', isset($client) && $client->relationship_start_date ? $client->relationship_start_date->format('Y-m-d') : '')" />
        <x-input-error :messages="$errors->get('relationship_start_date')" class="mt-2" />
    </div>
</div>

<div class="flex items-center justify-end gap-4 mt-6">
    <a href="{{ route('clients.index') }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
    <x-primary-button>{{ $submit }}</x-primary-button>
</div>