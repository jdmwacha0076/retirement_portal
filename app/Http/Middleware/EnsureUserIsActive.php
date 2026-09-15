<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsActive
{
    /**
     * Suspending a user previously only blocked *login* (see
     * FortifyServiceProvider::authenticateUsing()) - a session that was
     * already active kept full access until it expired on its own, even
     * after an admin suspended the account. Running this on every
     * request closes that gap: as soon as a logged-in user's status
     * flips away from 'active', their very next request logs them out.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && ! $user->isActive()) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'This account has been suspended. Contact an administrator.']);
        }

        return $next($request);
    }
}
