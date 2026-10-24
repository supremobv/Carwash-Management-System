<?php

namespace App\Providers;

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
        foreach (['operations','queue','customers','catalog','finance','reports','inventory','settings','users','audit'] as $permission) {
            \Illuminate\Support\Facades\Gate::define($permission, fn ($user) => $user->canDo($permission));
        }
        \Illuminate\Support\Facades\View::composer('*',function ($view) {
            if (str_starts_with($view->name(),'errors.')) return;
            $business=\App\Models\Setting::first();
            $view->with('business',$business)->with('money',fn($cents)=>($business->currency??'PHP').' '.number_format($cents/100,2));
        });
        \Illuminate\Pagination\Paginator::defaultView('partials.pagination');
    }
}
