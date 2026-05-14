<?php

declare(strict_types=1);

namespace App\Livewire\Actions;

class Logout
{
    /**
     * Log the current user out of the application.
     */
    public function __invoke()
    {
        auth()->guard('web')->logout();

        session()->invalidate();
        session()->regenerateToken();

        return redirect('/');
    }
}
