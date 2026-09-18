<?php

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use App\Http\Middleware\RecordWebLoginAuditLog;
use App\Http\Middleware\RecordWebLogoutAuditLog;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
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
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
        $this->configureAuditLogging();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn () => view('livewire.auth.login'));
        Fortify::confirmPasswordView(fn () => view('livewire.auth.confirm-password'));
        Fortify::resetPasswordView(fn () => view('livewire.auth.reset-password'));
        Fortify::requestPasswordResetLinkView(fn () => view('livewire.auth.forgot-password'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(5)->by($request->session()->get('login.id')));

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

    }

    /**
     * Chain audit-log middleware onto Fortify's own web login/logout routes
     * (D63/D93) — neither `AuthenticatedSessionController::store()` nor
     * `destroy()` exposes a swap point for this, so the routes are looked
     * up once every provider has booted (guaranteeing Fortify's own
     * `routes.php` has already registered them) and given the middleware
     * directly.
     */
    private function configureAuditLogging(): void
    {
        $this->app->booted(function (): void {
            // Fortify names its routes fluently (`->name('login.store')`) after
            // registering them, so `RouteCollection::$nameList` — only rebuilt
            // by `refreshNameLookups()` on the next Symfony conversion/URL
            // generation — is still stale here; `getByName()` would miss both
            // routes. Matching on the routes array directly sidesteps that.
            foreach (Route::getRoutes()->getRoutes() as $route) {
                match ($route->getName()) {
                    'login.store' => $route->middleware(RecordWebLoginAuditLog::class),
                    'logout' => $route->middleware(RecordWebLogoutAuditLog::class),
                    default => null,
                };
            }
        });
    }
}
