<?php

namespace App\Http\Controllers;

use App\Services\LoginIdentifierResolver;
use App\Services\SiteAvailabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MaintenanceAdministratorLoginController extends Controller
{
    public function create(SiteAvailabilityService $availability): View|RedirectResponse
    {
        if ($availability->activeSetting() === null) {
            return redirect('/admin/login');
        }

        return view('auth.maintenance-administrator-login');
    }

    public function store(
        Request $request,
        LoginIdentifierResolver $resolver,
        SiteAvailabilityService $availability,
    ): RedirectResponse {
        if ($availability->activeSetting() === null) {
            return redirect('/admin/login');
        }

        $credentials = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);
        $key = 'maintenance-admin-login:'.sha1(strtolower(trim($credentials['login'])).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'login' => 'Terlalu banyak percobaan masuk. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.',
            ]);
        }

        $user = $resolver->resolve($credentials['login']);

        if ($user === null || ! $user->isAdmin() || ! Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'login' => 'Akun Administrator atau kata sandi tidak sesuai.',
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        RateLimiter::clear($key);

        return redirect()->intended('/admin');
    }
}
