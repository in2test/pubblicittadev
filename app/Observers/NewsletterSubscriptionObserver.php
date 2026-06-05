<?php

declare(strict_types=1);

namespace App\Observers;

use App\Mail\NewsletterSubscribedAdminNotification;
use App\Models\NewsletterSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class NewsletterSubscriptionObserver
{
    /**
     * Handle the NewsletterSubscription "created" event.
     */
    public function created(NewsletterSubscription $newsletterSubscription): void
    {
        if (! $newsletterSubscription->is_active) {
            return;
        }

        $admins = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->where('is_active', true)
            ->get();

        foreach ($admins as $admin) {
            Mail::to($admin->email)->send(new NewsletterSubscribedAdminNotification($newsletterSubscription));
        }
    }

    /**
     * Handle the NewsletterSubscription "updated" event.
     */
    public function updated(NewsletterSubscription $newsletterSubscription): void
    {
        //
    }

    /**
     * Handle the NewsletterSubscription "deleted" event.
     */
    public function deleted(NewsletterSubscription $newsletterSubscription): void
    {
        //
    }

    /**
     * Handle the NewsletterSubscription "restored" event.
     */
    public function restored(NewsletterSubscription $newsletterSubscription): void
    {
        //
    }

    /**
     * Handle the NewsletterSubscription "force deleted" event.
     */
    public function forceDeleted(NewsletterSubscription $newsletterSubscription): void
    {
        //
    }
}
