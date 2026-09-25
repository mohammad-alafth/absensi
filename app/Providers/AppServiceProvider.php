<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\User;
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
        View::composer('dashboard', function ($view) {
            // Hanya kolom yang dipakai view (badge jumlah akun menunggu approval).
            $view->with('pendingUsers', User::where('is_approved', false)->select('id', 'name')->get());
        });
    }
}
