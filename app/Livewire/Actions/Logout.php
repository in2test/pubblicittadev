<?php

declare(strict_types=1);

namespace App\Livewire\Actions;

use Illuminate\Support\Facades\Auth;

class Logout
{
    /**
     * Log the current user out of the application.
     */
    public function __invoke()
    {
        Auth::guard('web')->logout();

        session()->invalidate();
        session()->regenerateToken();

        return redirect('/');
    }
}
