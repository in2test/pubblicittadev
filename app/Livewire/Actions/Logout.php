<?php

declare(strict_types=1);

namespace App\Livewire\Actions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class Logout
{
    /**
     * Log the current user out of the application.
     */
    public function __invoke()
    {
        (new Auth)->guard('web')->logout();

        (new Session)->invalidate();
        (new Session)->regenerateToken();

        return redirect('/');
    }
}
