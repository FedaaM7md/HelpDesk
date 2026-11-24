<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class LocaleController extends Controller
{
    public function switch(Request $request, string $locale): RedirectResponse
    {
        if (! in_array($locale, ['ar', 'en'], true)) {
            abort(404);
        }

        $request->session()->put('locale', $locale);
        App::setLocale($locale);

        $referer = $request->headers->get('referer');
        $appUrl = rtrim((string) config('app.url'), '/');

        if ($referer && $appUrl !== '' && str_starts_with($referer, $appUrl)) {
            return redirect()->to($referer);
        }

        return redirect()->route('home');
    }
}
