<div @style(['display: none' => !$showModal])>
@if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/50" wire:click="close"></div>
        <div class="relative z-10 w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
            <button wire:click="close" class="absolute right-4 top-4 text-gray-500 hover:text-gray-700">
                <span class="material-symbols-outlined">close</span>
            </button>

            <div class="mb-4 flex gap-4 border-b">
                <button
                    wire:click="switchMode('login')"
                    class="pb-2 {{ $mode === 'login' ? 'border-b-2 border-amber-500 font-bold text-gray-900' : 'text-gray-500' }}"
                >
                    Accedi
                </button>
                <button
                    wire:click="switchMode('register')"
                    class="pb-2 {{ $mode === 'register' ? 'border-b-2 border-amber-500 font-bold text-gray-900' : 'text-gray-500' }}"
                >
                    Registrati
                </button>
            </div>

            @if($error)
                <div class="mb-4 rounded bg-red-100 p-3 text-sm text-red-700">
                    {{ $error }}
                </div>
            @endif

            @if($mode === 'login')
                <form wire:submit.prevent="login" class="space-y-4">
                    <div>
                        <label for="auth-email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" id="auth-email" wire:model="email" required autocomplete="email"
                            class="mt-1 w-full rounded border border-gray-300 px-3 py-2" />
                    </div>
                    <div>
                        <label for="auth-password" class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" id="auth-password" wire:model="password" required autocomplete="current-password"
                            class="mt-1 w-full rounded border border-gray-300 px-3 py-2" />
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <a href="#" class="text-amber-600 hover:underline">Recupera credenziali</a>
                    </div>
                    <button type="submit"
                        class="w-full rounded bg-amber-500 py-2 text-white hover:bg-amber-600">
                        Accedi
                    </button>
                </form>
            @else
                <form wire:submit.prevent="register" class="space-y-4">
                    <div>
                        <label for="reg-name" class="block text-sm font-medium text-gray-700">Nome</label>
                        <input type="text" id="reg-name" wire:model="name" required autocomplete="name"
                            class="mt-1 w-full rounded border border-gray-300 px-3 py-2" />
                    </div>
                    <div>
                        <label for="reg-email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" id="reg-email" wire:model="email" required autocomplete="email"
                            class="mt-1 w-full rounded border border-gray-300 px-3 py-2" />
                    </div>
                    <div>
                        <label for="reg-password" class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" id="reg-password" wire:model="password" required autocomplete="new-password"
                            class="mt-1 w-full rounded border border-gray-300 px-3 py-2" />
                    </div>
                    <div>
                        <label for="reg-password-confirm" class="block text-sm font-medium text-gray-700">Conferma Password</label>
                        <input type="password" id="reg-password-confirm" wire:model="passwordConfirmation" required autocomplete="new-password"
                            class="mt-1 w-full rounded border border-gray-300 px-3 py-2" />
                    </div>
                    <button type="submit"
                        class="w-full rounded bg-amber-500 py-2 text-white hover:bg-amber-600">
                        Registrati
                    </button>
                </form>
            @endif
        </div>
    </div>
@endif
</div>