<?php

use App\Concerns\PasswordValidationRules;
use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component {
    use PasswordValidationRules;

    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => $this->currentPasswordRules(),
        ]);

        tap(auth()->user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<flux:modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable class="max-w-lg" style="border-radius: 0px !important;">
    <form method="POST" wire:submit="deleteUser" class="p-6 bg-gray-50 border-4 border-gray-950 shadow-2xl shadow-gray-950/20 space-y-6">
        <div>
            <h3 class="text-lg font-black uppercase tracking-wider text-red-700">{{ __('Sei sicuro di voler eliminare il tuo account?') }}</h3>
            <p class="text-xs text-gray-500 mt-2 font-mono">
                {{ __('Una volta eliminato il tuo account, tutte le sue risorse e i suoi dati verranno eliminati in modo permanente. Inserisci la tua password per confermare la cancellazione.') }}
            </p>
        </div>

        <div class="space-y-2">
            <label class="block text-[10px] font-mono uppercase tracking-widest text-gray-400">Password</label>
            <input type="password" wire:model="password" class="w-full bg-gray-50 border-2 border-gray-950 p-3 text-xs font-bold uppercase tracking-wider focus:border-secondary focus:ring-0 transition-colors" required />
            @error('password') <p class="text-xs text-red-600 font-mono mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="flex justify-end gap-3 border-t border-gray-200 pt-6">
            <flux:modal.close>
                <button type="button" class="px-4 py-2.5 border-2 border-gray-950 text-xs font-black uppercase tracking-wider bg-gray-50 hover:bg-gray-100 transition-colors">Annulla</button>
            </flux:modal.close>
            <button type="submit" class="px-4 py-2.5 bg-red-700 text-gray-50 border-2 border-red-700 text-xs font-black uppercase tracking-wider hover:bg-red-800 transition-colors" data-test="confirm-delete-user-button">Elimina Account</button>
        </div>
    </form>
</flux:modal>
