<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
class AuthController extends Controller
{
    public function login(Request $r)
    {
        $data=$r->validate(['email'=>'required|email|max:255','password'=>'required|string|max:255']);
        $key='login:'.hash('sha256',strtolower($data['email']).'|'.$r->ip());
        if (RateLimiter::tooManyAttempts($key,5)) throw ValidationException::withMessages(['email'=>'Too many attempts. Try again in '.RateLimiter::availableIn($key).' seconds.']);
        if (!Auth::attempt([...$data,'active'=>true])) {
            RateLimiter::hit($key,60);
            throw ValidationException::withMessages(['email'=>'The email or password is incorrect, or the account is inactive.']);
        }
        RateLimiter::clear($key); $r->session()->regenerate();
        \App\Support\Audit::record('Signed in','Account');
        return redirect()->intended('/');
    }
    public function logout(Request $r)
    {
        Auth::logout(); $r->session()->invalidate(); $r->session()->regenerateToken(); return redirect('/login');
    }
}
