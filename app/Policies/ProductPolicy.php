<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function toggleActive(User $user, Product $product): bool
    {
        return $user->canAccessFilament();
    }

    public function sync(User $user, Product $product): bool
    {
        return $user->canAccessFilament();
    }
}
