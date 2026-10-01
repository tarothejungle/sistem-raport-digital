<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\SiteAvailabilityService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventAccessDuringSiteMaintenance
{
    public function __construct(private readonly SiteAvailabilityService $availability) {}

    public function handle(Request $request, Closure $next): Response
    {
        $setting = $this->availability->activeSetting();

        if ($setting === null || $request->user()?->isAdmin()) {
            return $next($request);
        }

        if ($request->is('maintenance/administrator')) {
            return $next($request);
        }

        if ($request->user() instanceof User) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()
            ->view('maintenance', ['setting' => $setting], 503)
            ->header('Retry-After', (string) max(1, (int) ($setting->ends_at?->diffInSeconds(now(), true) ?? 3600)));
    }
}
