<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Carbon::setLocale(config('app.locale', 'id'));
        Paginator::useBootstrapFive();

        View::composer(['layouts.public', 'layouts.admin'], function ($view): void {
            $user = auth()->user();
            $view->with([
                'navCategories' => \App\Models\Category::query()->where('is_active', true)->orderBy('sort_order')->get(),
                'unreadCount' => $user ? $user->appNotifications()->whereNull('read_at')->count() : 0,
                'currentLocation' => \App\Support\UserLocation::current(),
                'compareIds' => session('compare', []),
            ]);
        });
    }
}
