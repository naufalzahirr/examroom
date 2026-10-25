<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ExamRoomHeaders
{
    public function handle(Request $r, Closure $next)
    {
        $response = $next($r);
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self' https://accounts.google.com/gsi/client; style-src 'self' 'unsafe-inline' https://accounts.google.com; frame-src https://accounts.google.com; connect-src 'self' https://accounts.google.com; img-src 'self' data: https://*.googleusercontent.com; font-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'");

        return $response;
    }
}
