<?php

use App\Concerns\PasswordValidationRules;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Security settings')] class extends Component {
    use PasswordValidationRules;

    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public bool $canManageTwoFactor;

    public bool $twoFactorEnabled;

    public bool $requiresConfirmation;

    /**
     * Mount the component.
     */
    public function mount(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $this->canManageTwoFactor = Features::canManageTwoFactorAuthentication();

        if ($this->canManageTwoFactor) {
            if (Fortify::confirmsTwoFactorAuthentication() && is_null(auth()->user()->two_factor_confirmed_at)) {
                $disableTwoFactorAuthentication(auth()->user());
            }

            $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
            $this->requiresConfirmation = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
        }
    }

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        auth()->user()->update([
            'password' => $validated['password'],
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
    }

    /**
     * Handle the two-factor authentication enabled event.
     */
    #[On('two-factor-enabled')]
    public function onTwoFactorEnabled(): void
    {
        $this->twoFactorEnabled = true;
    }

    /**
     * Disable two-factor authentication for the user.
     */
    public function disable(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $disableTwoFactorAuthentication(auth()->user());

        $this->twoFactorEnabled = false;
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('Update password')" :subheading="__('Ensure your account is using a long, random password to stay secure')">
        <form method="POST" wire:submit="updatePassword" class="mt-6 space-y-6">
            <div class="space-y-2">
                <label class="block text-[10px] font-mono uppercase tracking-widest text-gray-400">{{ __('Current password') }}</label>
                <input wire:model="current_password" type="password" required autocomplete="current-password" 
                       class="w-full bg-gray-50 border-2 border-gray-950 p-3 text-xs font-bold uppercase tracking-wider focus:border-secondary focus:ring-0 transition-colors" />
                @error('current_password') <p class="text-xs text-red-600 font-mono mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-2" x-data="{ 
                password: '',
                get hasMinLength() { return this.password.length >= 8 },
                get hasMixedCase() { return /[a-z]/.test(this.password) && /[A-Z]/.test(this.password) },
                get hasNumber() { return /[0-9]/.test(this.password) },
                get hasSymbol() { return /[^A-Za-z0-9]/.test(this.password) }
            }">
                <label class="block text-[10px] font-mono uppercase tracking-widest text-gray-400">{{ __('New password') }}</label>
                <input wire:model="password" @input="password = $event.target.value" type="password" required autocomplete="new-password" placeholder="Es. P@ssword123 (min. 8 caratteri, maiuscola, numero e simbolo)" 
                       class="w-full bg-gray-50 border-2 border-gray-950 p-3 text-xs font-bold uppercase tracking-wider focus:border-secondary focus:ring-0 transition-colors" />
                @error('password') <p class="text-xs text-red-600 font-mono mt-1">{{ $message }}</p> @enderror

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

            <div class="space-y-2">
                <label class="block text-[10px] font-mono uppercase tracking-widest text-gray-400">{{ __('Confirm password') }}</label>
                <input wire:model="password_confirmation" type="password" required autocomplete="new-password" 
                       class="w-full bg-gray-50 border-2 border-gray-950 p-3 text-xs font-bold uppercase tracking-wider focus:border-secondary focus:ring-0 transition-colors" />
                @error('password_confirmation') <p class="text-xs text-red-600 font-mono mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-4 pt-4">
                <button type="submit" class="px-6 py-3 bg-secondary text-gray-50 border-2 border-gray-950 text-xs font-black uppercase tracking-wider hover:bg-gray-950 transition-colors" data-test="update-password-button">
                    {{ __('Save') }}
                </button>

                <x-action-message class="text-xs font-bold uppercase tracking-wider text-green-600" on="password-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        @if ($canManageTwoFactor)
            <section class="mt-12 border-t border-gray-200 pt-12 space-y-6">
                <div>
                    <h3 class="text-sm font-black uppercase tracking-tight text-gray-950">{{ __('Two-factor authentication') }}</h3>
                    <p class="text-xs text-gray-500 mt-1 font-mono">{{ __('Manage your two-factor authentication settings') }}</p>
                </div>

                <div class="w-full mx-auto space-y-6 text-sm" wire:cloak>
                    @if ($twoFactorEnabled)
                        <div class="space-y-4">
                            <p class="text-xs text-gray-700 leading-relaxed font-mono">
                                {{ __('You will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.') }}
                            </p>

                            <div class="flex justify-start">
                                <button type="button" wire:click="disable" class="px-4 py-2.5 bg-red-50 text-red-700 border-2 border-red-700 text-[10px] font-black uppercase tracking-wider hover:bg-red-700 hover:text-gray-50 transition-colors">
                                    {{ __('Disable 2FA') }}
                                </button>
                            </div>

                            <livewire:pages::settings.two-factor.recovery-codes :$requiresConfirmation />
                        </div>
                    @else
                        <div class="space-y-4">
                            <p class="text-xs text-gray-500 leading-relaxed font-mono">
                                {{ __('When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.') }}
                            </p>

                            <flux:modal.trigger name="two-factor-setup-modal">
                                <button type="button" wire:click="$dispatch('start-two-factor-setup')" class="px-4 py-2.5 bg-secondary text-gray-50 border-2 border-gray-950 text-[10px] font-black uppercase tracking-wider hover:bg-gray-950 transition-colors">
                                    {{ __('Enable 2FA') }}
                                </button>
                            </flux:modal.trigger>

                            <livewire:pages::settings.two-factor-setup-modal :requires-confirmation="$requiresConfirmation" />
                        </div>
                    @endif
                </div>
            </section>
        @endif
    </x-pages::settings.layout>
</section>
