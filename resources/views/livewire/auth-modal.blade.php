<div>
    @if($showModal)
        <div class="fixed inset-0 z-100 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-gray-500/75 transition-opacity" wire:click="close"></div>

            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-gray-100">
                    
                    <!-- Close Button -->
                    <button type="button" wire:click="close" class="absolute right-4 top-4 text-gray-400 hover:text-gray-500 transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>

                    <div class="bg-white px-8 pt-10 pb-8">
                        <div class="space-y-6">
                            <div class="flex flex-col items-center justify-center text-center">
                                <h2 class="text-2xl font-black uppercase tracking-widest text-gray-900">{{ $mode === 'login' ? 'Accedi' : 'Registrati' }}</h2>
                                <p class="mt-1 text-sm text-gray-500">Benvenuto su Abbigliamento Personalizzato</p>
                            </div>

                            @if($error)
                                <div class="rounded-lg bg-red-50 p-4 border border-red-100">
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

                            <div class="flex gap-4 border-b border-gray-100">
                                <button
                                    wire:click="switchMode('login')"
                                    class="pb-3 text-sm font-bold uppercase tracking-wider transition-all {{ $mode === 'login' ? 'border-b-2 border-amber-500 text-gray-900' : 'text-gray-400 hover:text-gray-600' }}"
                                >
                                    Accedi
                                </button>
                                <button
                                    wire:click="switchMode('register')"
                                    class="pb-3 text-sm font-bold uppercase tracking-wider transition-all {{ $mode === 'register' ? 'border-b-2 border-amber-500 text-gray-900' : 'text-gray-400 hover:text-gray-600' }}"
                                >
                                    Registrati
                                </button>
                            </div>

                            @if($mode === 'login')
                                <form wire:submit.prevent="login" class="space-y-5">
                                    <div>
                                        <label for="email" class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Email</label>
                                        <input type="email" id="email" wire:model="email" required placeholder="Inserisci la tua email" 
                                            class="block w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-900 focus:border-amber-500 focus:ring-amber-500 transition-all shadow-sm">
                                        @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <div class="flex justify-between mb-1">
                                            <label for="password" class="block text-xs font-bold uppercase tracking-wider text-gray-700">Password</label>
                                            <a href="#" class="text-[10px] font-bold uppercase tracking-widest text-amber-600 hover:text-amber-700">Dimenticata?</a>
                                        </div>
                                        <input type="password" id="password" wire:model="password" required placeholder="••••••••" 
                                            class="block w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-900 focus:border-amber-500 focus:ring-amber-500 transition-all shadow-sm">
                                        @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="pt-2">
                                        <button type="submit" class="group relative flex w-full justify-center rounded-xl bg-gray-900 px-4 py-4 text-sm font-bold uppercase tracking-widest text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition-all shadow-lg active:scale-[0.98]">
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
                                            class="block w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-900 focus:border-amber-500 focus:ring-amber-500 transition-all shadow-sm">
                                        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <label for="reg_email" class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Email</label>
                                        <input type="email" id="reg_email" wire:model="email" required placeholder="La tua email migliore" 
                                            class="block w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-900 focus:border-amber-500 focus:ring-amber-500 transition-all shadow-sm">
                                        @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <label for="reg_password" class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Password</label>
                                        <input type="password" id="reg_password" wire:model="password" required placeholder="Minimo 8 caratteri" 
                                            class="block w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-900 focus:border-amber-500 focus:ring-amber-500 transition-all shadow-sm">
                                        @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <label for="reg_password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">Conferma Password</label>
                                        <input type="password" id="reg_password_confirmation" wire:model="password_confirmation" required placeholder="Ripeti la password" 
                                            class="block w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-900 focus:border-amber-500 focus:ring-amber-500 transition-all shadow-sm">
                                        @error('password_confirmation') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="pt-2">
                                        <button type="submit" class="group relative flex w-full justify-center rounded-xl bg-gray-900 px-4 py-4 text-sm font-bold uppercase tracking-widest text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition-all shadow-lg active:scale-[0.98]">
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