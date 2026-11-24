<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class DemoLoginController extends Controller
{
    /**
     * Sign a visitor in instantly as a demo account for the chosen role.
     * The route exists only while DEMO_MODE is enabled.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        abort_unless((bool) config('helpdesk.demo'), 404);

        $key = 'demo-login:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            throw ValidationException::withMessages([
                'role' => __('auth.throttled', ['seconds' => RateLimiter::availableIn($key)]),
            ]);
        }

        RateLimiter::hit($key, 60);

        $data = $request->validate([
            'role' => ['required', 'in:admin,supervisor,agent'],
        ]);

        $email = config('helpdesk.demo_accounts.'.$data['role']);
        $user = User::query()
            ->where('email', $email)
            ->where('is_active', true)
            ->first();

        abort_unless($user !== null, 404);

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('locale', $user->locale);
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('app.dashboard'));
    }
}
