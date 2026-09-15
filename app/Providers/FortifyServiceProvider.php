<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponse::class, function () {
            return new class implements LoginResponse
            {
                /**
                 * Fortify's default LoginResponse always redirects to a
                 * single fixed 'home' path. Kept as a role match (rather
                 * than a flat redirect) so a second role-specific area
                 * added later is a one-line change here, matching the
                 * pattern used in the other portals.
                 */
                public function toResponse($request)
                {
                    $user = $request->user();

                    return redirect()->intended(match ($user->role) {
                        'admin', 'staff' => route('dashboard'),
                        default => route('dashboard'),
                    });
                }
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        // Same credential check Fortify would do by default, plus the
        // suspended-account gate that isn't part of its pipeline - status
        // is a separate concern from "did the password match".
        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('email', $request->email)->first();

            if (! $user || ! Hash::check($request->password, $user->password)) {
                return null;
            }

            if (! $user->isActive()) {
                throw ValidationException::withMessages([
                    Fortify::username() => 'This account has been suspended. Contact an administrator.',
                ]);
            }

            return $user;
        });

        Fortify::loginView(fn () => view('auth.login'));

        // Self-registration is open (Features::registration() in
        // config/fortify.php), but every account it creates is 'staff' -
        // see App\Actions\Fortify\CreateNewUser, which hardcodes the role
        // rather than trusting the request.
        Fortify::registerView(fn () => view('auth.register'));

        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
        Fortify::resetPasswordView(fn ($request) => view('auth.reset-password', ['request' => $request]));

        // recordLogin() needs the request IP and needs to fire on every
        // successful login regardless of which path authenticated the
        // user - a listener on the standard Login event covers this
        // without duplicating the call inside authenticateUsing() too.
        Event::listen(Login::class, function (Login $event) {
            if ($event->user instanceof User) {
                $event->user->recordLogin(request()->ip());
            }
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('passkeys', function (Request $request) {
            $credentialId = $request->input('credential.id');

            return Limit::perMinute(10)->by(
                ($credentialId ?: $request->session()->getId()).'|'.$request->ip()
            );
        });
    }
}
