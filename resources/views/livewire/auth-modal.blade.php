<div>
    <flux:modal wire:model="showModal" class="w-full max-w-md">
        <div class="space-y-6">
            <div class="flex flex-col items-center justify-center text-center">
                <flux:heading size="xl" class="uppercase tracking-widest">{{ $mode === 'login' ? 'Accedi' : 'Registrati' }}</flux:heading>
                <flux:subheading>Benvenuto su Abbigliamento Personalizzato</flux:subheading>
            </div>

            @if($error)
                <flux:callout variant="danger" icon="exclamation-triangle">
                    {{ $error }}
                </flux:callout>
            @endif

            <div class="flex gap-4 border-b border-neutral-100 dark:border-neutral-800">
                <button
                    wire:click="switchMode('login')"
                    class="pb-3 text-sm font-bold uppercase tracking-wider transition-all {{ $mode === 'login' ? 'border-b-2 border-amber-500 text-gray-900 dark:text-white' : 'text-neutral-400 hover:text-neutral-600' }}"
                >
                    Accedi
                </button>
                <button
                    wire:click="switchMode('register')"
                    class="pb-3 text-sm font-bold uppercase tracking-wider transition-all {{ $mode === 'register' ? 'border-b-2 border-amber-500 text-gray-900 dark:text-white' : 'text-neutral-400 hover:text-neutral-600' }}"
                >
                    Registrati
                </button>
            </div>

            @if($mode === 'login')
                <form wire:submit.prevent="login" class="space-y-4">
                    <flux:field>
                        <flux:label>Email</flux:label>
                        <flux:input type="email" wire:model="email" required placeholder="Inserisci la tua email" />
                        <flux:error name="email" />
                    </flux:field>

                    <flux:field>
                        <div class="flex justify-between">
                            <flux:label>Password</flux:label>
                            <a href="#" class="text-xs text-amber-600 hover:underline">Dimenticata?</a>
                        </div>
                        <flux:input type="password" wire:model="password" required viewable placeholder="••••••••" />
                        <flux:error name="password" />
                    </flux:field>

                    <div class="pt-2">
                        <flux:button type="submit" variant="primary" class="w-full">
                            Entra
                        </flux:button>
                    </div>
                </form>
            @else
                <form wire:submit.prevent="register" class="space-y-4">
                    <flux:field>
                        <flux:label>Nome Completo</flux:label>
                        <flux:input wire:model="name" required placeholder="Nome e Cognome" />
                        <flux:error name="name" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Email</flux:label>
                        <flux:input type="email" wire:model="email" required placeholder="La tua email migliore" />
                        <flux:error name="email" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Password</flux:label>
                        <flux:input type="password" wire:model="password" required viewable placeholder="Minimo 8 caratteri" />
                        <flux:error name="password" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Conferma Password</flux:label>
                        <flux:input type="password" wire:model="passwordConfirmation" required placeholder="Ripeti la password" />
                        <flux:error name="passwordConfirmation" />
                    </flux:field>

                    <div class="pt-2">
                        <flux:button type="submit" variant="primary" class="w-full">
                            Crea Account
                        </flux:button>
                    </div>
                </form>
            @endif
        </div>
    </flux:modal>
</div>