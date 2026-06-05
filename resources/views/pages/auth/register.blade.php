<x-layouts::auth :title="__('Register')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Name -->
            <flux:input
                name="name"
                :label="__('Name')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Full name')"
            />

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <div x-data="{ 
                password: '',
                get hasMinLength() { return this.password.length >= 8 },
                get hasMixedCase() { return /[a-z]/.test(this.password) && /[A-Z]/.test(this.password) },
                get hasNumber() { return /[0-9]/.test(this.password) },
                get hasSymbol() { return /[^A-Za-z0-9]/.test(this.password) }
            }">
                <flux:input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="new-password"
                    placeholder="Es. P@ssword123 (min. 8 caratteri, maiuscola, numero e simbolo)"
                    viewable
                    @input="password = $event.target.value"
                />

                <div class="mt-3 p-3 border-2 border-gray-950 bg-gray-50 text-[11px] font-mono uppercase tracking-wider space-y-2">
                    <p class="font-black text-gray-950">Requisiti password:</p>
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm font-black" :class="hasMinLength ? 'text-emerald-600' : 'text-accent-500'" x-text="hasMinLength ? 'check' : 'close'"></span>
                        <span :class="hasMinLength ? 'text-emerald-600 font-bold' : 'text-gray-400'">Almeno 8 caratteri</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm font-black" :class="hasMixedCase ? 'text-emerald-600' : 'text-accent-500'" x-text="hasMixedCase ? 'check' : 'close'"></span>
                        <span :class="hasMixedCase ? 'text-emerald-600 font-bold' : 'text-gray-400'">Maiuscole e minuscole</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm font-black" :class="hasNumber ? 'text-emerald-600' : 'text-accent-500'" x-text="hasNumber ? 'check' : 'close'"></span>
                        <span :class="hasNumber ? 'text-emerald-600 font-bold' : 'text-gray-400'">Almeno un numero</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm font-black" :class="hasSymbol ? 'text-emerald-600' : 'text-accent-500'" x-text="hasSymbol ? 'check' : 'close'"></span>
                        <span :class="hasSymbol ? 'text-emerald-600 font-bold' : 'text-gray-400'">Almeno un carattere speciale</span>
                    </div>
                </div>
            </div>

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Confirm password')"
                viewable
            />

            <!-- Subscribe to Newsletter -->
            <flux:checkbox
                name="subscribe_to_newsletter"
                :label="__('Iscriviti alla newsletter')"
                :checked="old('subscribe_to_newsletter')"
            />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                    {{ __('Create account') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-gray-600">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
