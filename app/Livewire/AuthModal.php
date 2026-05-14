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

    public string $passwordConfirmation = '';

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
        $this->reset(['email', 'password', 'name', 'passwordConfirmation', 'error']);
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
            'passwordConfirmation' => 'required',
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

    public function logout(): void
    {
        /** @var StatefulGuard $guard */
        $guard = auth();

        $guard->logout();
        $this->close();
    }

    public function render()
    {
        return view('livewire.auth-modal');
    }
}
