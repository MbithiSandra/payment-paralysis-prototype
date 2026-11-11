<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
                // Strong passwords on register, reset password and change password
        Password::defaults(function () {
            return Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols();
        });
    }
}
