<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public const SUPPORTED = ['ar', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale');

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = config('app.locale');
        }

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = 'ar';
        }

        App::setLocale($locale);
        Carbon::setLocale($locale);
        $request->session()->put('locale', $locale);

        view()->share('locale', $locale);
        view()->share('direction', $locale === 'ar' ? 'rtl' : 'ltr');

        return $next($request);
    }
}
