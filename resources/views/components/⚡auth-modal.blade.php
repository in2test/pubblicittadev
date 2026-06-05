<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use App\Models\User;
use Illuminate\Contracts\Auth\StatefulGuard;

new class extends Component {
    public bool $showModal = false;
    public string $mode = 'login';
    public string $email = '';
    public string $password = '';
    public string $name = '';
    public string $password_confirmation = '';
    public string $error = '';
    public bool $subscribe_to_newsletter = false;

    #[Computed]
    public function isAuthenticated(): bool
    {
        return auth()->check();
    }

    #[Computed]
    public function currentUser(): ?User
    {
        $user = auth()->user();
        return $user instanceof User ? $user : null;
    }

    #[On('open-auth-modal')]
    public function open(): void
    {
        $this->showModal = true;
        $this->error = '';
    }

    public function close(): void
    {
        $this->showModal = false;
        $this->reset(['email', 'password', 'name', 'password_confirmation', 'error']);
    }

    public function switchMode(string $mode): void
    {
        $this->mode = $mode;
        $this->error = '';
    }

    public function login(): void
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = [
            'email' => $this->email,
            'password' => $this->password,
        ];

        /** @var StatefulGuard $guard */
        $guard = auth();

        if ($guard->attempt($credentials)) {
            $this->close();
            $this->js('window.location.reload()');

            return;
        }

        $this->error = 'Credenziali non valide.';
    }

    public function register(): void
    {
        $this->validate([
            'name' => 'required|min:2',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::default()],
            'password_confirmation' => 'required',
            'subscribe_to_newsletter' => 'boolean',
        ]);

        $isFirstUser = User::count() === 0;

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'role' => $isFirstUser ? User::ROLE_ADMIN : User::ROLE_CLIENT,
            'is_active' => true,
        ]);

        event(new \Illuminate\Auth\Events\Registered($user));

        if ($this->subscribe_to_newsletter) {
            \App\Models\NewsletterSubscription::updateOrCreate(
                ['email' => $this->email],
                ['is_active' => true]
            );
        }

        /** @var StatefulGuard $guard */
        $guard = auth();

        $guard->login($user);
        $this->close();
        $this->js('window.location.reload()');
    }

    public function messages(): array
    {
        return [
            'email.required' => "L'indirizzo email è obbligatorio.",
            'email.email' => 'Inserisci un indirizzo email valido.',
            'email.unique' => 'Questa email è già registrata.',
            'password.required' => 'La password è obbligatoria.',
            'password.min' => 'La password deve avere almeno :min caratteri.',
            'password.confirmed' => 'La conferma della password non coincide.',
            'name.required' => 'Il nome è obbligatorio.',
            'name.min' => 'Il nome deve avere almeno :min caratteri.',
        ];
    }
};
?>

<div>
    @if($showModal)
        <div class="fixed inset-0 z-100 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-gray-500/75 transition-opacity" wire:click="close"></div>

            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden bg-gray-50 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md border-2 border-gray-950">
                    
                    <!-- Close Button -->
                    <button type="button" wire:click="close" class="absolute right-4 top-4 text-gray-400 hover:text-gray-950 transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>

                    <div class="bg-gray-50 px-8 pt-10 pb-8">
                        <div class="space-y-6">
                            <div class="flex flex-col items-center justify-center text-center">
                                <h2 class="text-2xl font-black uppercase tracking-widest text-gray-950">{{ $mode === 'login' ? 'Accedi' : 'Registrati' }}</h2>
                                <p class="mt-1 text-sm text-gray-500">Benvenuto su {{ config('app.name') }}</p>
                            </div>

                            @if($error)
                                <div class="bg-red-50 p-4 border-2 border-red-950">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <span class="material-symbols-outlined text-red-400">warning</span>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-red-800">{{ $error }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="flex gap-4 border-b border-gray-200">
                                <button
                                    wire:click="switchMode('login')"
                                    class="pb-3 text-sm font-bold uppercase tracking-wider transition-all {{ $mode === 'login' ? 'border-b-2 border-accent-500 text-gray-950' : 'text-gray-400 hover:text-gray-600' }}"
                                >
                                    Accedi
                                </button>
                                <button
                                    wire:click="switchMode('register')"
                                    class="pb-3 text-sm font-bold uppercase tracking-wider transition-all {{ $mode === 'register' ? 'border-b-2 border-accent-500 text-gray-950' : 'text-gray-400 hover:text-gray-600' }}"
                                >
                                    Registrati
                                </button>
                            </div>

                            @if($mode === 'login')
                                <form wire:submit.prevent="login" class="space-y-5">
                                    <div>
                                        <label for="email" class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Email</label>
                                        <input type="email" id="email" wire:model="email" required placeholder="Inserisci la tua email" 
                                            class="block w-full border-2 border-gray-950 bg-gray-50 px-4 py-3 text-sm text-gray-950 focus:border-accent-500 focus:ring-0 transition-all">
                                        @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <div class="flex justify-between mb-1">
                                            <label for="password" class="block text-xs font-bold uppercase tracking-wider text-gray-700">Password</label>
                                            <a href="{{ route('password.request') }}" class="text-[10px] font-bold uppercase tracking-widest text-accent-500 hover:text-accent-700">Dimenticata?</a>
                                        </div>
                                        <input type="password" id="password" wire:model="password" required placeholder="••••••••" 
                                            class="block w-full border-2 border-gray-950 bg-gray-50 px-4 py-3 text-sm text-gray-950 focus:border-accent-500 focus:ring-0 transition-all">
                                        @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="pt-2">
                                        <button type="submit" class="group relative flex w-full justify-center bg-gray-950 px-4 py-4 text-sm font-bold uppercase tracking-widest text-gray-50 border-2 border-gray-950 hover:bg-accent-500 focus:outline-none focus:border-accent-500 transition-all shadow-lg active:scale-[0.98]">
                                            <span>Entra</span>
                                            <span class="absolute right-4 transition-transform group-hover:translate-x-1">
                                                <span class="material-symbols-outlined text-sm">arrow_forward</span>
                                            </span>
                                        </button>
                                    </div>
                                </form>
                            @else
                                <form wire:submit.prevent="register" class="space-y-5">
                                    <div>
                                        <label for="reg_name" class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Nome Completo</label>
                                        <input type="text" id="reg_name" wire:model="name" required placeholder="Nome e Cognome" 
                                            class="block w-full border-2 border-gray-950 bg-gray-50 px-4 py-3 text-sm text-gray-950 focus:border-accent-500 focus:ring-0 transition-all">
                                        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <label for="reg_email" class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Email</label>
                                        <input type="email" id="reg_email" wire:model="email" required placeholder="email@example.com" 
                                            class="block w-full border-2 border-gray-950 bg-gray-50 px-4 py-3 text-sm text-gray-950 focus:border-accent-500 focus:ring-0 transition-all">
                                        @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div x-data="{ 
                                        password: '',
                                        get hasMinLength() { return this.password.length >= 8 },
                                        get hasMixedCase() { return /[a-z]/.test(this.password) && /[A-Z]/.test(this.password) },
                                        get hasNumber() { return /[0-9]/.test(this.password) },
                                        get hasSymbol() { return /[^A-Za-z0-9]/.test(this.password) }
                                    }">
                                        <label for="reg_password" class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Password</label>
                                        <input type="password" id="reg_password" wire:model="password" @input="password = $event.target.value" required placeholder="Es. P@ssword123 (min. 8 caratteri, maiuscola, numero e simbolo)" 
                                            class="block w-full border-2 border-gray-950 bg-gray-50 px-4 py-3 text-sm text-gray-950 focus:border-accent-500 focus:ring-0 transition-all">
                                        @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

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

                                    <div>
                                        <label for="reg_password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Conferma Password</label>
                                        <input type="password" id="reg_password_confirmation" wire:model="password_confirmation" required placeholder="Ripeti la password" 
                                            class="block w-full border-2 border-gray-950 bg-gray-50 px-4 py-3 text-sm text-gray-950 focus:border-accent-500 focus:ring-0 transition-all">
                                        @error('password_confirmation') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="flex items-center gap-2 py-1">
                                        <input type="checkbox" id="reg_subscribe_to_newsletter" wire:model="subscribe_to_newsletter"
                                            class="border-2 border-gray-950 text-accent-500 focus:ring-0 transition-all h-4 w-4">
                                        <label for="reg_subscribe_to_newsletter" class="text-xs font-bold uppercase tracking-wider text-gray-700 select-none cursor-pointer">Iscriviti alla newsletter</label>
                                    </div>

                                    <div class="pt-2">
                                        <button type="submit" class="group relative flex w-full justify-center bg-gray-950 px-4 py-4 text-sm font-bold uppercase tracking-widest text-gray-50 border-2 border-gray-950 hover:bg-accent-500 focus:outline-none focus:border-accent-500 transition-all shadow-lg active:scale-[0.98]">
                                            <span>Crea Account</span>
                                            <span class="absolute right-4 transition-transform group-hover:translate-x-1">
                                                <span class="material-symbols-outlined text-sm">person_add</span>
                                            </span>
                                        </button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
