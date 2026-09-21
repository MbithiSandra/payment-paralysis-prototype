<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

                <!-- Enterprise Name -->
        <div>
            <x-input-label for="business_name" :value="__('Enterprise name')" />
            <x-text-input id="business_name" class="block mt-1 w-full" type="text" name="business_name" :value="old('business_name')" required autofocus />
            <x-input-error :messages="$errors->get('business_name')" class="mt-2" />
        </div>

        <!-- Enterprise KRA PIN -->
        <div class="mt-4">
            <x-input-label for="kra_pin" :value="__('Enterprise KRA PIN')" />
            <x-text-input id="kra_pin" class="block mt-1 w-full" type="text" name="kra_pin" :value="old('kra_pin')" required />
            <x-input-error :messages="$errors->get('kra_pin')" class="mt-2" />
        </div>

        <!-- Sector -->
        <div class="mt-4">
            <x-input-label for="sector" :value="__('Sector')" />
            <x-text-input id="sector" class="block mt-1 w-full" type="text" name="sector" :value="old('sector')" placeholder="e.g. Wholesale, Construction, Agribusiness" />
            <x-input-error :messages="$errors->get('sector')" class="mt-2" />
        </div>


        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
