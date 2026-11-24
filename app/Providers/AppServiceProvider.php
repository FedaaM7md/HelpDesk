<?php

// HelpDesk — © 2026 Fedaa Mohamed. All rights reserved. Proprietary; not open source.

namespace App\Providers;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->defineGates();
        $this->defineRateLimiters();
        $this->defineViewComposers();
    }

    protected function defineViewComposers(): void
    {
        View::composer('layouts.app', function ($view) {
            $user = auth()->user();

            if (! $user) {
                $view->with('navCounts', ['unresolved' => 0, 'unassigned' => 0]);

                return;
            }

            $base = Ticket::query()->visibleTo($user);

            $view->with('navCounts', [
                'unresolved' => (clone $base)->unresolved()->count(),
                'unassigned' => (clone $base)->unresolved()->whereNull('assigned_to')->count(),
            ]);
        });
    }

    protected function defineGates(): void
    {
        Gate::define('manage-users', fn (User $user) => $user->isAdmin());
        Gate::define('manage-settings', fn (User $user) => $user->isAdmin());
        Gate::define('view-reports', fn (User $user) => $user->isAdmin() || $user->isSupervisor());
        Gate::define('view-all-tickets', fn (User $user) => $user->canViewAllTickets());
        Gate::define('assign-tickets', fn (User $user) => $user->isAdmin() || $user->isSupervisor());
        Gate::define('manage-kb', fn (User $user) => $user->isAdmin() || $user->isSupervisor());
        Gate::define('manage-canned-replies', fn (User $user) => $user->isAdmin() || $user->isSupervisor());
        Gate::define('manage-taxonomy', fn (User $user) => $user->isAdmin() || $user->isSupervisor());
        Gate::define('delete-tickets', fn (User $user) => $user->isAdmin());
        Gate::define('view-audit', fn (User $user) => $user->isAdmin());
    }

    protected function defineRateLimiters(): void
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        RateLimiter::for('public-forms', fn (Request $request) => Limit::perMinute(6)->by($request->ip()));

        RateLimiter::for('tracking', function (Request $request) {
            return Limit::perMinute(10)->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip());
        });
    }
}
