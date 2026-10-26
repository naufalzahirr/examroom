<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        app()->setLocale('id');
        RateLimiter::for('exam-deletion', fn (Request $r) => [Limit::perMinute(5)->by('delete-ip:'.$r->ip()), Limit::perHour(3)->by('delete-email:'.hash('sha256', strtolower($r->input('email', ''))))]);
        RateLimiter::for('exam-auth', fn (Request $r) => [Limit::perMinute(60)->by('ip:'.$r->ip()), Limit::perMinute(20)->by('account:'.strtolower($r->input('email', ''))), Limit::perMinute(5)->by(strtolower($r->input('email', '')).'|'.$r->ip())]);
        RateLimiter::for('exam-codes', fn (Request $r) => Limit::perMinute(600)->by($r->ip()));
    }
}
