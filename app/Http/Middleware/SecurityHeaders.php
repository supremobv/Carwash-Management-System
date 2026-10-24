<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && !$request->user()->active) {
            auth()->logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
            return redirect('/login')->with('error','Your account is inactive. Contact your administrator.');
        }
        $response=$next($request);
        $response->headers->set('X-Content-Type-Options','nosniff');
        $response->headers->set('X-Frame-Options','DENY');
        $response->headers->set('Referrer-Policy','same-origin');
        $response->headers->set('Permissions-Policy','camera=(), microphone=(), geolocation=()');
        $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; font-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
        $response->headers->set('Cache-Control','no-store, private');
        if ($request->secure()) $response->headers->set('Strict-Transport-Security','max-age=31536000');
        return $response;
    }
}
