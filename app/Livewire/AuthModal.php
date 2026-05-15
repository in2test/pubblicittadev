<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\User;
use Illuminate\Contracts\Auth\StatefulGuard;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class AuthModal extends Component
{
    public bool $showModal = false;

    public string $mode = 'login';

    public string $email = '';

    public string $password = '';

    public string $name = '';

    public string $password_confirmation = '';

    public string $error = '';

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
            'password' => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'role' => User::ROLE_CLIENT,
            'is_active' => true,
        ]);

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

    public function render()
    {
        return view('livewire.auth-modal');
    }
}
