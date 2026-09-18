<?php

namespace App\Http\Middleware;

use App\Concerns\WritesAuditLog;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RecordWebLogoutAuditLog
{
    use WritesAuditLog;

    /**
     * Fortify's own `AuthenticatedSessionController::destroy()` invalidates
     * the session itself with no swap point (D93) — the acting user has to
     * be captured before `$next` runs, since `destroy()` clears the guard.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = Auth::guard('web')->user();

        $response = $next($request);

        if ($user) {
            $this->recordAuditLog($request, $user, 'logout');
        }

        return $response;
    }
}
