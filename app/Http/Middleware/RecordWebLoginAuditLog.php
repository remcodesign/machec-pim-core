<?php

namespace App\Http\Middleware;

use App\Concerns\WritesAuditLog;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RecordWebLoginAuditLog
{
    use WritesAuditLog;

    /**
     * Fortify's own `AuthenticatedSessionController::store()` has no swap
     * point for the successfully-authenticated user (D93) — this middleware
     * is chained onto the `login.store` route instead and only records the
     * login once `$next` confirms the session was actually established.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (Auth::guard('web')->check()) {
            /** @var User $user */
            $user = Auth::user();

            $this->recordAuditLog($request, $user, 'login');
        }

        return $response;
    }
}
