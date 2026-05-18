<?php

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Profile settings')] class extends Component {
    use ProfileValidationRules;

    public string $name = '';
    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = auth()->user()->name;
        $this->email = auth()->user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = auth()->user();

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = auth()->user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        session()->flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return auth()->user() instanceof MustVerifyEmail && ! auth()->user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! auth()->user() instanceof MustVerifyEmail
            || (auth()->user() instanceof MustVerifyEmail && auth()->user()->hasVerifiedEmail());
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <div class="space-y-2">
                <label class="block text-[10px] font-mono uppercase tracking-widest text-gray-400">Nome</label>
                <input wire:model="name" type="text" required autofocus autocomplete="name" 
                       class="w-full bg-gray-50 border-2 border-gray-950 p-3 text-xs font-bold uppercase tracking-wider focus:border-secondary focus:ring-0 transition-colors" />
                @error('name') <p class="text-xs text-red-600 font-mono mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-2">
                <label class="block text-[10px] font-mono uppercase tracking-widest text-gray-400">Email</label>
                <input wire:model="email" type="email" required autocomplete="email" 
                       class="w-full bg-gray-50 border-2 border-gray-950 p-3 text-xs font-bold uppercase tracking-wider focus:border-secondary focus:ring-0 transition-colors" />
                @error('email') <p class="text-xs text-red-600 font-mono mt-1">{{ $message }}</p> @enderror

                @if ($this->hasUnverifiedEmail)
                    <div class="mt-4 p-4 border border-dashed border-gray-300 bg-gray-50">
                        <p class="text-xs text-gray-600 font-mono">
                            {{ __('Your email address is unverified.') }}
                            <button type="button" wire:click.prevent="resendVerificationNotification" class="text-secondary hover:underline ml-1 font-bold">
                                {{ __('Click here to re-send the verification email.') }}
                            </button>
                        </p>

                        @if (session('status') === 'verification-link-sent')
                            <p class="mt-2 text-xs font-bold uppercase tracking-wider text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4 pt-4">
                <button type="submit" class="px-6 py-3 bg-secondary text-gray-50 border-2 border-gray-950 text-xs font-black uppercase tracking-wider hover:bg-gray-950 transition-colors" data-test="update-profile-button">
                    {{ __('Salva') }}
                </button>

                <x-action-message class="text-xs font-bold uppercase tracking-wider text-green-600" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        @if ($this->showDeleteUser)
            <livewire:pages::settings.delete-user-form />
        @endif
    </x-pages::settings.layout>
</section>
